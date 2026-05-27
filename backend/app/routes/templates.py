from fastapi import APIRouter, Depends, HTTPException
from sqlmodel import Session, select
from typing import List
from ..database import get_session
from ..models import Template

router = APIRouter(prefix="/templates", tags=["templates"])

@router.post("/", response_model=Template)
def create_template(template: Template, session: Session = Depends(get_session)):
    session.add(template)
    session.commit()
    session.refresh(template)
    return template

@router.get("/", response_model=List[Template])
def read_templates(session: Session = Depends(get_session)):
    templates = session.exec(select(Template)).all()
    return templates

@router.get("/{template_id}", response_model=Template)
def read_template(template_id: int, session: Session = Depends(get_session)):
    template = session.get(Template, template_id)
    if not template:
        raise HTTPException(status_code=404, detail="Template not found")
    return template
