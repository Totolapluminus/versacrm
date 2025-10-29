FROM python:3.12-alpine

RUN apk add --no-cache ca-certificates
WORKDIR /app

COPY bot/requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

RUN pip install requests

COPY bot/ /app/
ENV PYTHONUNBUFFERED=1 PIP_NO_CACHE_DIR=1
CMD ["python", "main.py"]