import os
import sys
import time
import telebot
import requests

TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
if not TOKEN:
    raise RuntimeError("TELEGRAM_BOT_TOKEN is not set")

LARAVEL_API_URL = os.getenv("LARAVEL_API_URL")

bot = telebot.TeleBot(TOKEN, parse_mode="HTML")  # можно без parse_mode

@bot.message_handler(commands=["start", "help"])
def cmd_start(message: telebot.types.Message):
    bot.reply_to(
        message,
        "Привет! Я бот CRM на telebot.\nНапиши сообщение — я сохраню его."
    )

@bot.message_handler(content_types=["text"])
def on_text(message: telebot.types.Message):
    text = message.text or ""
    user_id = message.from_user.id if message.from_user else None
    user_username = message.from_user.username if message.from_user else None
    chat_id = message.chat.id
    chat_type = message.chat.type

    payload = {
        'user_id': user_id,
        'user_username': user_username,
        'chat_id': chat_id,
        'chat_type': chat_type,
        'direction': 'in',
        'text': text
    }

    print(f"[BOT] Received message: {payload}", flush=True)

    try:
        print(f"[BOT] Sending POST to {LARAVEL_API_URL}/api/messages ...", flush=True)
        response = requests.post(
            f'{LARAVEL_API_URL}/api/messages',
            data=payload,
            timeout=5  # ограничим время ожидания
        )
        print(f"[BOT] Got response: {response.status_code} {response.text}", flush=True)

        if response.status_code == 200:
            bot.reply_to(message, f"Сообщение сохранено: {text}")
        else:
            bot.reply_to(message, f"Ошибка API: {response.status_code}")
    except Exception as e:
        print(f"[BOT] ERROR during request: {e}", file=sys.stderr, flush=True)
        bot.reply_to(message, f"Ошибка при подключении к API: {e}")

def main():
    # Бесконечный цикл с авто-перезапуском polling при сетевых сбоях
    while True:
        try:
            # none_stop=True — не останавливать обработку при ошибках
            # interval=0 — не ждать между циклами
            bot.infinity_polling(timeout=30, long_polling_timeout=25)
        except KeyboardInterrupt:
            print("KeyboardInterrupt — выходим", file=sys.stderr, flush=True)
            break
        except Exception as e:
            # Логируем и пробуем перезапуститься через паузу
            print(f"[BOT] polling error: {e}", file=sys.stderr, flush=True)
            time.sleep(5)

if __name__ == "__main__":
    main()
