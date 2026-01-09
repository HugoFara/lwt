from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    piper_voices_dir: str = "/app/voices"
    piper_voices_url: str = "https://huggingface.co/rhasspy/piper-voices/resolve/main"

    class Config:
        env_prefix = ""


settings = Settings()
