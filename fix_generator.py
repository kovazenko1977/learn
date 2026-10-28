import re

# Read template and build exact 36 slides
with open("generate_veselovo_presentation.py", "r", encoding="utf-8") as f:
    code = f.read()

# Let's write a clean script that assembles slides in exact order 1..36
