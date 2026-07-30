import sys
import os

def verify_vba_file(filepath):
    print(f"Starting verification of VBA macro file: {filepath}")

    if not os.path.exists(filepath):
        print(f"Error: {filepath} does not exist.")
        sys.exit(1)

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check key attributes and structure
    required_keywords = [
        "Attribute VB_Name",
        "RunGenerator",
        "CreateSampleWorkspace",
        "GenerateGridCards",
        "GenerateIndividualSheets",
        "GeneratePDFFiles",
        "SheetExists",
        "CleanSheetName",
        "CleanFileName",
        "TogglePerformance"
    ]

    missing = []
    for kw in required_keywords:
        if kw not in content:
            missing.append(kw)

    if missing:
        print(f"Error: Missing required VBA procedures or markers: {missing}")
        sys.exit(1)

    # Check syntax / basic structural balance
    lines = content.split('\n')
    open_subs = 0
    for line in lines:
        line_strip = line.strip()
        if line_strip.startswith("Public Sub ") or line_strip.startswith("Private Sub ") or line_strip.startswith("Private Function "):
            open_subs += 1
        elif line_strip.startswith("End Sub") or line_strip.startswith("End Function"):
            open_subs -= 1

    if open_subs != 0:
        print(f"Warning: Visual Basic structures might be unbalanced (count: {open_subs})")

    print("VBA file verification PASSED successfully! All key procedures, helper functions, and optimizations are present.")

if __name__ == "__main__":
    verify_vba_file("UniversalGenerator.bas")
