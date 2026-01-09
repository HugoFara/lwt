FROM python:3.11-slim-bookworm

# Install system dependencies for MeCab and Piper
RUN apt-get update && apt-get install -y --no-install-recommends \
    mecab mecab-ipadic-utf8 libmecab-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app/ ./app/

# Create voices directory
RUN mkdir -p /app/voices

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
