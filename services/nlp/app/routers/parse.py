from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from app.services.parsers import mecab, jieba
from app.services.parsers.base import ParseResult

router = APIRouter()


class ParseRequest(BaseModel):
    text: str
    parser: str  # 'mecab' | 'jieba'


@router.post("/", response_model=ParseResult)
async def parse(request: ParseRequest):
    """Parse text into sentences and tokens."""
    if request.parser == 'mecab':
        return mecab.parse(request.text)
    elif request.parser == 'jieba':
        return jieba.parse(request.text)
    else:
        raise HTTPException(400, f"Unknown parser: {request.parser}")


@router.get("/available")
async def available_parsers():
    """List available parsers."""
    return {
        "parsers": [
            {"id": "mecab", "name": "MeCab (Japanese)", "languages": ["ja"]},
            {"id": "jieba", "name": "Jieba (Chinese)", "languages": ["zh"]},
        ]
    }
