import sys
import os

def check_barcode_routines(filepath):
    print(f"Running automated check on upgraded macro: {filepath}")

    if not os.path.exists(filepath):
        print(f"Error: {filepath} does not exist.")
        sys.exit(1)

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check key barcode and image saving functions
    required_routines = [
        "PromptAndGenerateBarcodeImage",
        "DrawCode39Vector",
        "GetCode39Pattern",
        "ExportShapeToPNG",
        "msoShapeRectangle",
        "ChartObjects.Add",
        "FilterName:=\"PNG\""
    ]

    missing = []
    for routine in required_routines:
        if routine not in content:
            missing.append(routine)

    if missing:
        print(f"Error: Missing required barcode procedures: {missing}")
        sys.exit(1)

    print("All barcode generation and image saving procedures are successfully verified!")

if __name__ == "__main__":
    check_barcode_routines("UniversalGenerator.bas")
