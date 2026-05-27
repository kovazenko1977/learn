import pandas as pd
import io
from typing import List
from ..models.models import Product

def parse_excel_products(file_content: bytes) -> List[dict]:
    df = pd.read_excel(io.BytesIO(file_content))
    products = df.to_dict(orient='records')
    return products

def parse_csv_products(file_content: bytes) -> List[dict]:
    df = pd.read_csv(io.BytesIO(file_content))
    products = df.to_dict(orient='records')
    return products
