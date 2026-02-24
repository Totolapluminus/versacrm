# multi_bot.py
# pyTelegramBotAPI==4.24.0

import os
import sys
import time
import threading
import secrets
import io
import mimetypes
from dataclasses import dataclass
from typing import Optional, Dict, Tuple

import requests
import telebot
from telebot import types

LARAVEL_API_URL = os.getenv("LARAVEL_API_URL", "").rstrip("/")
SANCTUM_TOKEN = os.getenv("SANCTUM_TOKEN", "")

CATEGORIES = [
    {"key": "login",          "title": "🔐 Не могу войти"},
    {"key": "access",         "title": "🧩 Нет доступа к разделам"},
    {"key": "profile_update", "title": "🔁 Не работает смена данных"},
    {"key": "notifications",  "title": "🔔 Не приходят уведомления"},
    {"key": "bug",            "title": "⚠️ Ошибка / баг в кабинете"},
    {"key": "other",          "title": "❓ Другое"},
]

CATEGORY_BY_TITLE = {c["title"]: c["key"] for c in CATEGORIES}
CATEGORY_TITLE_BY_KEY = {c["key"]: c["title"] for c in CATEGORIES}
CATEGORY_TITLES = set(CATEGORY_BY_TITLE.keys())

DOMAINS = [
    {"key": "lms",      "title": "🎓 Портал СДО lms.melsu.ru"},
    {"key": "rasp",     "title": "🗓️ Расписание rasp.melsu.ru"},
    {"key": "my",       "title": "🔑 Личный кабинет my.melsu.ru"},
    {"key": "other",    "title": "❓ Другое"},
]

DOMAIN_BY_TITLE = {d["title"]: d["key"] for d in DOMAINS}
DOMAIN_TITLE_BY_KEY = {d["key"]: d["title"] for d in DOMAINS}
DOMAIN_TITLES = set(DOMAIN_BY_TITLE.keys())

CANCEL_TEXT = "❌ Отмена"
NEW_TICKET_TEXT = "❌ Закрыть заявку"


def generate_ticket_id() -> str:
    return f"№-H-{secrets.randbelow(1_000_000):06d}"

session = requests.Session()
headers = {
    "Authorization": f"Bearer {SANCTUM_TOKEN}",
    "Accept": "application/json",
}


def api_get_bots() -> list[dict]:
    if not LARAVEL_API_URL:
        raise RuntimeError("LARAVEL_API_URL is empty")
    if not SANCTUM_TOKEN:
        raise RuntimeError("SANCTUM_TOKEN is empty")

    resp = session.get(f"{LARAVEL_API_URL}/api/telegram-bots", headers=headers, timeout=15)
    resp.raise_for_status()
    return resp.json() or []


def api_get_chat_status(bot_db_id: int, tg_chat_id: int):
    try:
        r = requests.get(
            f"{LARAVEL_API_URL}/api/telegram/chat-status",
            params={"telegram_bot_id": bot_db_id, "chat_id": tg_chat_id},
            headers=headers,
            timeout=10,
        )
        r.raise_for_status()
        return r.json()  # {exists, chat_db_id, status}
    except Exception:
        return None


def api_post_message(payload: dict, label: str) -> requests.Response:
    url = f"{LARAVEL_API_URL}/api/messages"

    # Логи ДО отправки
    print(f"[{label}] POST {url}", flush=True)
    print(f"[{label}] Payload: {payload}", flush=True)

    try:
        r = session.post(url, headers=headers, json=payload, timeout=15)
    except Exception as e:
        print(f"[{label}] POST error: {e}", file=sys.stderr, flush=True)
        raise


    print(f"[{label}] Response status: {r.status_code}", flush=True)
    print(f"[{label}] Response text: {r.text}", flush=True)

    r.raise_for_status()
    return r


def api_post_message_with_attachment(payload: dict, file_bytes: bytes, filename: str, mime: str, label: str) -> requests.Response:
    url = f"{LARAVEL_API_URL}/api/messages"

    print(f"[{label}] POST {url} (multipart)", flush=True)
    print(f"[{label}] Payload: {payload}", flush=True)
    print(f"[{label}] Attachment: {filename} ({mime}), {len(file_bytes)} bytes", flush=True)

    files = {
        "attachment": (filename, io.BytesIO(file_bytes), mime),
    }

    # ВАЖНО: payload передаем как data, не json
    r = session.post(url, headers=headers, data=payload, files=files, timeout=60)

    print(f"[{label}] Response status: {r.status_code}", flush=True)
    print(f"[{label}] Response text: {r.text}", flush=True)

    r.raise_for_status()
    return r


def api_close_active_chat(bot_db_id: int, tg_chat_id: int):
    try:
        r = requests.post(
            f"{LARAVEL_API_URL}/api/telegram/close-chat",
            json={"telegram_bot_id": bot_db_id, "chat_id": tg_chat_id},
            headers=headers,
            timeout=10,
        )
        r.raise_for_status()
        return r.json()
    except Exception:
        return None

@dataclass
class UserState:
    # choose_domain -> choose_category -> wait_text -> chat
    step: str
    ticket_domain: Optional[str] = None
    category_key: Optional[str] = None
    ticket_id: Optional[str] = None


# key = (bot_db_id, telegram_user_id)
user_states: Dict[Tuple[int, int], UserState] = {}


def reset_new_ticket(bot_db_id: int, user_id: int) -> None:
    user_states[(bot_db_id, user_id)] = UserState(
        step="choose_domain",
        ticket_domain=None,
        category_key=None,
        ticket_id=generate_ticket_id()
    )


def build_categories_keyboard() -> types.ReplyKeyboardMarkup:
    kb = types.ReplyKeyboardMarkup(resize_keyboard=True, one_time_keyboard=True)
    row = []
    for c in CATEGORIES:
        row.append(types.KeyboardButton(c["title"]))
        if len(row) == 2:
            kb.row(*row)
            row = []
    if row:
        kb.row(*row)
    kb.row(types.KeyboardButton(CANCEL_TEXT))
    return kb

def build_domains_keyboard() -> types.ReplyKeyboardMarkup:
    kb = types.ReplyKeyboardMarkup(resize_keyboard=True, one_time_keyboard=True)
    row = []
    for d in DOMAINS:
        row.append(types.KeyboardButton(d["title"]))
        if len(row) == 2:
            kb.row(*row)
            row = []
    if row:
        kb.row(*row)
    kb.row(types.KeyboardButton(CANCEL_TEXT))
    return kb


def build_chat_keyboard() -> types.ReplyKeyboardMarkup:
    kb = types.ReplyKeyboardMarkup(resize_keyboard=True)
    kb.row(types.KeyboardButton(NEW_TICKET_TEXT))
    return kb

def build_cancel_keyboard() -> types.ReplyKeyboardMarkup:
    kb = types.ReplyKeyboardMarkup(resize_keyboard=True, one_time_keyboard=True)
    kb.row(types.KeyboardButton(CANCEL_TEXT))
    return kb


def make_bot(token: str, bot_db_id: int) -> tuple[telebot.TeleBot, str]:
    bot = telebot.TeleBot(token, parse_mode="HTML")

    try:
        me = bot.get_me()
        label = f"{me.id}@{me.username}"
        bot_tg_id = me.id
        bot_username = me.username
    except Exception as e:
        print("get_me failed:", repr(e), flush=True)
        label = f"bot_db_{bot_db_id}"
        bot_tg_id = None
        bot_username = None

    def build_crm_payload(message: types.Message, text: str, state: UserState) -> dict:
        return {
            "user_id": message.from_user.id if message.from_user else None,
            "user_username": message.from_user.username if message.from_user else None,
            "user_first_name": message.from_user.first_name if message.from_user else None,
            "user_last_name": message.from_user.last_name if message.from_user else None,

            "chat_id": message.chat.id,
            "chat_type": message.chat.type,

            "direction": "in",
            "text": text or "",

            "bot_tg_id": bot_tg_id,
            "bot_db_id": bot_db_id,
            "bot_username": bot_username,

            "ticket_type": state.category_key,
            "ticket_id": state.ticket_id,
            "ticket_domain": state.ticket_domain,
        }

    def download_file_bytes(file_id: str) -> tuple[bytes, str, str]:
            f = bot.get_file(file_id)
            file_path = f.file_path  # например "photos/file_123.jpg"
            file_bytes = bot.download_file(file_path)

            # имя файла и mime по расширению из file_path
            filename = os.path.basename(file_path) or "image"
            mime, _ = mimetypes.guess_type(filename)
            mime = mime or "application/octet-stream"
            return file_bytes, filename, mime

    def extract_attachment(message: types.Message) -> tuple[bytes, str, str] | None:
            # photo
            if getattr(message, "photo", None):
                file_id = message.photo[-1].file_id  # самое большое
                file_bytes, filename, mime = download_file_bytes(file_id)

                # Телега иногда даёт .jpg, иногда без расширения — подстрахуемся
                if mime == "application/octet-stream":
                    mime = "image/jpeg"
                    if "." not in filename:
                        filename += ".jpg"
                return file_bytes, filename, mime

            # document (если юзер прислал как файл)
            doc = getattr(message, "document", None)
            if doc:
                # отсекаем всё кроме разрешенных картинок
                mime = doc.mime_type or ""
                if mime not in ("image/jpeg", "image/png", "image/gif", "image/webp"):
                    return None
                file_bytes, filename, _ = download_file_bytes(doc.file_id)
                filename = doc.file_name or filename or "image"
                return file_bytes, filename, mime
            return None

    def ask_choose_domain (message: types.Message) -> None:
        bot.send_message(
            message.chat.id,
            "Выберите сайт с которым у вас проблема 👇\n"
            'Если хотите обратиться по другому поводу - нажмите "❓ Другое".',
            reply_markup=build_domains_keyboard()
        )

    def ask_choose_category(message: types.Message, ticket_domain_key: str) -> None:
        bot.send_message(
            message.chat.id,
            f"Сайт выбран: <b>{DOMAIN_TITLE_BY_KEY.get(ticket_domain_key, ticket_domain_key)}</b>\n\n"
            "Выберите категорию проблемы кнопкой ниже 👇\n"
            'Если не нашли свою категорию - нажмите "❓ Другое".',
            reply_markup=build_categories_keyboard()
        )

    def ask_problem_text(message: types.Message, category_key: str, ticket_domain_key: str) -> None:
        bot.send_message(
            message.chat.id,
            f"Сайт выбран: <b>{DOMAIN_TITLE_BY_KEY.get(ticket_domain_key, ticket_domain_key)}</b>\n"
            f"Категория выбрана: <b>{CATEGORY_TITLE_BY_KEY.get(category_key, category_key)}</b>\n\n"
            "Теперь ПОДРОБНО ОПИШИТЕ ПРОБЛЕМУ одним сообщением.\n\n"
            "В нем укажите ваше ФИО, а также EMAIL или ЛОГИН на сайте.\n"
            "Это обязательно для оператора.\n\n"
            "Если вы хотите ОТПРАВИТЬ ФОТО,\n" "отправьте его СЛЕДУЮЩИМ СООБЩЕНИЕМ\n\n"
            "Заявка попадёт оператору и он ответит вам в ближайшее время.",
            reply_markup=build_cancel_keyboard()
        )

    @bot.message_handler(commands=["start", "help"])
    def cmd_start(message: types.Message):
        if not message.from_user:
            return
        api_close_active_chat(bot_db_id, message.chat.id)
        reset_new_ticket(bot_db_id, message.from_user.id)
        ask_choose_domain(message)

    @bot.message_handler(func=lambda m: (m.text or "").strip() == NEW_TICKET_TEXT, content_types=["text"])
    def on_new_ticket_button(message: types.Message):
        if not message.from_user:
            return
        api_close_active_chat(bot_db_id, message.chat.id)
        reset_new_ticket(bot_db_id, message.from_user.id)
        ask_choose_domain(message)

    @bot.message_handler(commands=["cancel"])
    def cmd_cancel(message: types.Message):
        if not message.from_user:
            return
        # отмена = начать заново с выбором категории для новой заявки
        reset_new_ticket(bot_db_id, message.from_user.id)
        bot.send_message(
            message.chat.id,
            "Ок, отменил. Выберите сайт заново 👇",
            reply_markup=build_domains_keyboard()
        )

    @bot.message_handler(func=lambda m: (m.text or "").strip() == CANCEL_TEXT, content_types=["text"])
    def on_cancel_button(message: types.Message):
        cmd_cancel(message)

    @bot.message_handler(content_types=["text"])
    def on_text(message: types.Message):
        if not message.from_user:
            return

        user_id = message.from_user.id
        key = (bot_db_id, user_id)
        text = (message.text or "").strip()

        # если бот перезапустился и state пропал — выбрать категорию заново
        state = user_states.get(key)
        if state is None:
            reset_new_ticket(bot_db_id, user_id)
            bot.send_message(
                message.chat.id,
                "⚠️ Похоже, бот был перезапущен.\n"
                "Пожалуйста, выберите сайт заново 👇",
                reply_markup=build_domains_keyboard()
            )
            return

        # если категория/тикет_id пустые — перезапустить
        if not state.ticket_id:
            reset_new_ticket(bot_db_id, user_id)
            bot.send_message(
                message.chat.id,
                "⚠️ Не удалось восстановить заявку. Начнём заново.\n"
                "Выберите сайт 👇",
                reply_markup=build_domains_keyboard()
            )
            return

        if state.step == "choose_domain":
            if text in DOMAIN_TITLES:
                state.ticket_domain = DOMAIN_BY_TITLE[text]
                state.step = "choose_category"
                user_states[key] = state
                ask_choose_category(message, state.ticket_domain)
                return

            ask_choose_domain(message)
            return

        if state.step == "choose_category":
            if not state.ticket_domain:
                state.step = "choose_domain"
                user_states[key] = state
                ask_choose_domain(message)
                return

            if text in CATEGORY_TITLES:
                state.category_key = CATEGORY_BY_TITLE[text]
                state.step = "wait_text"
                user_states[key] = state
                ask_problem_text(message, state.category_key, state.ticket_domain)
                return

            ask_choose_category(message, state.ticket_domain)
            return

        if state.step == "wait_text":
            if not state.ticket_domain:
                state.step = "choose_domain"
                user_states[key] = state
                ask_choose_domain(message)
                return

            if not text:
                bot.send_message(message.chat.id, "Сообщение пустое. Опишите проблему текстом.")
                return

            if not state.category_key:
                # на всякий случай
                state.step = "choose_category"
                user_states[key] = state
                ask_choose_category(message, state.ticket_domain)
                return

            payload = build_crm_payload(message, text, state)

            try:
                api_post_message(payload, label=label)
            except Exception as e:
                bot.reply_to(message, f"⚠️ Ошибка при подключении к API: {e}")
                return

            # дальше свободный чат в рамках этой заявки
            state.step = "chat"
            user_states[key] = state

            bot.send_message(
                message.chat.id,
                "✅ Заявка отправлена оператору.\n"
                f"Номер: <b>{state.ticket_id}</b>\n"
                "Можете продолжать переписку в этом чате.\n\n"
                "Если нужна новая заявка или вопрос решен — нажмите «❌ Закрыть заявку».",
                reply_markup=build_chat_keyboard()
            )
            return

        if state.step == "chat":
            if not state.category_key:
                # если по какой-то причине потеряли category_key — просим выбрать заново
                state.step = "choose_category"
                state.category_key = None
                user_states[key] = state
                bot.send_message(
                    message.chat.id,
                    "⚠️ Я не вижу выбранной категории для этой заявки.\n"
                    "Пожалуйста, выберите категорию заново 👇",
                    reply_markup=build_categories_keyboard()
                )
                return

            payload = build_crm_payload(message, text, state)

            try:
                info = api_get_chat_status(bot_db_id, message.chat.id)
                if info and info.get("chat_status") == "closed":
                    bot.reply_to(
                        message,
                        "Заявка закрыта. \nЕсли нужно — создайте новую, нажав «❌ Закрыть заявку» или /start."
                    )
                    return
                else: api_post_message(payload, label=label)
            except Exception as e:
                bot.reply_to(message, f"⚠️ Ошибка при подключении к API: {e}")
                return

            # bot.send_message(message.chat.id, "✅ Сообщение отправлено оператору.")
            return

        reset_new_ticket(bot_db_id, user_id)
        ask_choose_domain(message)

    @bot.message_handler(content_types=["photo", "document"])
    def on_media(message: types.Message):

        print(f"[{label}] GOT MEDIA: content_type={message.content_type}", flush=True)

        if not message.from_user:
            return

        user_id = message.from_user.id
        key = (bot_db_id, user_id)

        state = user_states.get(key)
        if state is None:
            reset_new_ticket(bot_db_id, user_id)
            bot.send_message(
                message.chat.id,
                "⚠️ Похоже, бот был перезапущен.\n"
                "Пожалуйста, выберите сайт заново 👇",
                reply_markup=build_domains_keyboard()
            )
            return

        if not state.ticket_id:
            reset_new_ticket(bot_db_id, user_id)
            bot.send_message(
                message.chat.id,
                "⚠️ Не удалось восстановить заявку. Начнём заново.\n"
                "Выберите сайт 👇",
                reply_markup=build_domains_keyboard()
            )
            return

        att = extract_attachment(message)
        if not att:
            bot.reply_to(message, "⚠️ Я принимаю только изображения: JPG / PNG / GIF / WEBP (до 15MB).")
            return

        file_bytes, filename, mime = att
        text = (getattr(message, "caption", None) or "").strip()

        # На этапах выбора — просто просим выбрать
        if state.step in ("choose_domain", "choose_category"):
            if state.step == "choose_domain":
                ask_choose_domain(message)
            else:
                if state.ticket_domain:
                    ask_choose_category(message, state.ticket_domain)
                else:
                    state.step = "choose_domain"
                    user_states[key] = state
                    ask_choose_domain(message)
            return

        # На wait_text: фото может быть ПЕРВЫМ сообщением заявки (без текста тоже ок)
        if state.step == "wait_text":
            if not state.ticket_domain:
                state.step = "choose_domain"
                user_states[key] = state
                ask_choose_domain(message)
                return

            if not state.category_key:
                state.step = "choose_category"
                user_states[key] = state
                ask_choose_category(message, state.ticket_domain)
                return

            payload = build_crm_payload(message, text, state)

            try:
                api_post_message_with_attachment(payload, file_bytes, filename, mime, label=label)
            except Exception as e:
                bot.reply_to(message, f"⚠️ Ошибка при подключении к API: {e}")
                return

            state.step = "chat"
            user_states[key] = state

            bot.send_message(
                message.chat.id,
                "✅ Заявка отправлена оператору.\n"
                f"Номер: <b>{state.ticket_id}</b>\n"
                "Можете продолжать переписку в этом чате.\n\n"
                "Если нужна новая заявка или вопрос решен — нажмите «❌ Закрыть заявку».",
                reply_markup=build_chat_keyboard()
            )
            return

        # В чате заявки — просто отправляем вложение
        if state.step == "chat":
            if not state.category_key:
                state.step = "choose_category"
                state.category_key = None
                user_states[key] = state
                bot.send_message(
                    message.chat.id,
                    "⚠️ Я не вижу выбранной категории для этой заявки.\n"
                    "Пожалуйста, выберите категорию заново 👇",
                    reply_markup=build_categories_keyboard()
                )
                return

            try:
                info = api_get_chat_status(bot_db_id, message.chat.id)
                if info and info.get("chat_status") == "closed":
                    bot.reply_to(
                        message,
                        "Заявка закрыта.\nЕсли нужно — создайте новую, нажав «❌ Закрыть заявку» или /start."
                    )
                    return

                payload = build_crm_payload(message, text, state)
                api_post_message_with_attachment(payload, file_bytes, filename, mime, label=label)

            except Exception as e:
                bot.reply_to(message, f"⚠️ Ошибка при подключении к API: {e}")
                return

            return

        # fallback
        reset_new_ticket(bot_db_id, user_id)
        ask_choose_domain(message)
    return bot, label


def poll_worker(bot: telebot.TeleBot, label: str):
    while True:
        try:
            print(f"[{label}] start polling", flush=True)
            bot.infinity_polling(timeout=30, long_polling_timeout=25)
        except KeyboardInterrupt:
            print(f"[{label}] stop", flush=True)
            break
        except Exception as e:
            print(f"[{label}] polling error: {e}", file=sys.stderr, flush=True)
            time.sleep(5)


def main():
    bots_data = api_get_bots()
    print(f"[CORE] Loaded bots: {len(bots_data)}", flush=True)

    threads: list[threading.Thread] = []

    for item in bots_data:
        token = item.get("token")
        bot_db_id = item.get("id")
        if not token or not bot_db_id:
            continue

        bot, label = make_bot(token, int(bot_db_id))
        t = threading.Thread(target=poll_worker, args=(bot, label), daemon=True)
        t.start()
        threads.append(t)

    # KEEP ALIVE
    try:
        while any(t.is_alive() for t in threads):
            time.sleep(1)
    except KeyboardInterrupt:
        print("[CORE] Exit", flush=True)


if __name__ == "__main__":
    main()
