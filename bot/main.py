import os
import sys
import time
import telebot
import requests
import threading

LARAVEL_API_URL = os.getenv("LARAVEL_API_URL")
SANCTUM_TOKEN = os.getenv("SANCTUM_TOKEN")

headers = {
    "Authorization": f"Bearer {SANCTUM_TOKEN}",
}

#GET ALL BOTS
resp = requests.get(f"{LARAVEL_API_URL}/api/telegram-bots", headers=headers, timeout=10)
resp.raise_for_status()
bots_data = resp.json() or []

def make_bot(token: str, id: int):

    bot = telebot.TeleBot(token, parse_mode="HTML")

    try:
        me = bot.get_me()
        label = f"{me.id}@{me.username}"
    except Exception as e:
        print("get_me failed:", repr(e))
        me, label = None, "bot"

    @bot.message_handler(commands=["start", "help"])

    def cmd_start(message: telebot.types.Message):
        bot.reply_to(message, "Привет! Я бот CRM на telebot.\nНапиши сообщение — я сохраню его.")

    @bot.message_handler(content_types=["text"])

    def on_text(message: telebot.types.Message):

        payload = {
            'user_id': message.from_user.id if message.from_user else None,
            'user_username': message.from_user.username if message.from_user else None,
            'chat_id': message.chat.id,
            'chat_type': message.chat.type,
            'direction': 'in',
            'text': message.text or "",
            'bot_tg_id': me.id if me else None,
            'bot_db_id': id if id else None,
            'bot_username': me.username if me else None,
        }

        print(f"[{label}] Received: {payload}", flush=True)

        try:
            r = requests.post(f"{LARAVEL_API_URL}/api/messages", data=payload, timeout=10)
            print(f"[{label}] API: {r.status_code} {r.text}", flush=True)
            bot.reply_to(message, "Сообщение сохранено: " + (message.text or "")) if r.status_code == 200 \
                else bot.reply_to(message, f"Ошибка API: {r.status_code}")
        except Exception as e:
            print(f"[{label}] POST error: {e}", file=sys.stderr, flush=True)
            bot.reply_to(message, f"Ошибка при подключении к API: {e}")

    return bot, label

def poll_worker(bot: telebot.TeleBot, label: str):
    while True:
        try:
            print(f"[{label}] start polling", flush=True)
            bot.infinity_polling(timeout=30, long_polling_timeout=25)
        except KeyboardInterrupt:
            print(f"[{label}] stop", flush=True); break
        except Exception as e:
            print(f"[{label}] polling error: {e}", file=sys.stderr, flush=True)
            time.sleep(5)

#INIT AND START ALL BOTS
threads = []
for item in bots_data:

    token = item.get("token")
    id = item.get("id")
    if not token:
        continue
    bot, label = make_bot(token, id)
    thread = threading.Thread(target=poll_worker, args=(bot, label), daemon=True)
    thread.start()
    threads.append(thread)

#KEEP ALIVE
try:
    while any(thread.is_alive() for thread in threads):
        time.sleep(1)
except KeyboardInterrupt:
    print("[CORE] Exit", flush=True)
