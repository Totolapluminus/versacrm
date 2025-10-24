import os
import sys
import time
import telebot

TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
if not TOKEN:
    raise RuntimeError("TELEGRAM_BOT_TOKEN is not set")

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
    chat_id = message.chat.id

    # TODO: здесь пишем в БД или шлём в ваш Laravel API
    # пример логов:
    # print(f"[BOT] from {user_id} in {chat_id}: {text}", flush=True)

    # Пока просто эхо:
    bot.reply_to(message, f"Получил: {text}")

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
