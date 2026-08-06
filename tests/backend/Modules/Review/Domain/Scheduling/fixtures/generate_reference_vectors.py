"""Generate FSRS-6 ground-truth vectors from py-fsrs for the PHP port's tests.

Configured with learning_steps=() / relearning_steps=() so the reference goes
straight to the Review state — the same simplification the PHP port makes.

Pinned to fsrs==6.3.1. The version matters: py-fsrs's unreleased `main` widens
the short-term stability clamp from (Good, Easy) to (Hard, Good, Easy), which
changes same-day-repeat results materially. Regenerate against a different
release only together with the matching change in Fsrs6Scheduler.

    python3 -m venv .venv
    ./.venv/bin/pip install 'fsrs==6.3.1'
    ./.venv/bin/python generate_reference_vectors.py > fsrs6_reference_vectors.json
"""

import json
from datetime import datetime, timedelta, timezone

from fsrs import Card, Rating, Scheduler

scheduler = Scheduler(learning_steps=(), relearning_steps=(), enable_fuzzing=False)

START = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)

# (name, [(rating, days_to_advance_before_this_review), ...])
SEQUENCES = [
    ("all_good", [(Rating.Good, 0), (Rating.Good, 3), (Rating.Good, 10)]),
    ("all_again", [(Rating.Again, 0), (Rating.Again, 1), (Rating.Again, 1)]),
    ("hard_path", [(Rating.Hard, 0), (Rating.Hard, 2), (Rating.Good, 5)]),
    ("easy_path", [(Rating.Easy, 0), (Rating.Easy, 15), (Rating.Good, 40)]),
    ("lapse_then_recover", [(Rating.Good, 0), (Rating.Good, 7), (Rating.Again, 9), (Rating.Good, 1)]),
    ("same_day_repeat", [(Rating.Good, 0), (Rating.Good, 0), (Rating.Hard, 0)]),
    ("mixed", [(Rating.Good, 0), (Rating.Hard, 4), (Rating.Easy, 12), (Rating.Again, 30), (Rating.Good, 2)]),
]

out = []
for name, steps in SEQUENCES:
    card = Card()
    now = START
    reviews = []
    for rating, advance in steps:
        now = now + timedelta(days=advance)
        retr_before = scheduler.get_card_retrievability(card, current_datetime=now)
        card, _log = scheduler.review_card(card, rating, review_datetime=now)
        interval = (card.due - now).days
        reviews.append(
            {
                "grade": int(rating),
                "advance_days": advance,
                "retrievability_before": round(retr_before, 12),
                "stability": round(card.stability, 12),
                "difficulty": round(card.difficulty, 12),
                "interval_days": interval,
            }
        )
    out.append({"name": name, "reviews": reviews})

print(json.dumps(out, indent=2))
