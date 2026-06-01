# -*- coding: utf-8 -*-
import json
import os
from datetime import datetime
from typing import Any, Dict, Optional

SESSION_DIR = "sessions"


class SessionManager:
    def __init__(self, session_dir: str = SESSION_DIR):
        self.session_dir = session_dir
        os.makedirs(self.session_dir, exist_ok=True)

    def create_session(self, project_name: str) -> Dict[str, Any]:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        session_id = f"{project_name}_{timestamp}"
        session = {
            "session_id": session_id,
            "project": project_name,
            "created_at": timestamp,
            "updated_at": timestamp,
            "status": "active",
            "current_task": None,
            "completed_tasks": [],
            "artifacts": [],
            "context": {},
            "task_outputs": {},
        }
        path = os.path.join(self.session_dir, f"{session_id}.json")
        with open(path, "w", encoding="utf-8") as f:
            json.dump(session, f, ensure_ascii=False, indent=2)
        return session

    def load_session(self, session_id: str) -> Optional[Dict[str, Any]]:
        path = os.path.join(self.session_dir, f"{session_id}.json")
        if not os.path.exists(path):
            return None
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)

    def save_session(self, session: Dict[str, Any]) -> None:
        session["updated_at"] = datetime.now().strftime("%Y%m%d_%H%M%S")
        path = os.path.join(self.session_dir, f"{session['session_id']}.json")
        with open(path, "w", encoding="utf-8") as f:
            json.dump(session, f, ensure_ascii=False, indent=2)

    def list_sessions(self) -> list:
        sessions = []
        if not os.path.exists(self.session_dir):
            return sessions
        for fname in sorted(os.listdir(self.session_dir), reverse=True):
            if fname.endswith(".json"):
                path = os.path.join(self.session_dir, fname)
                with open(path, "r", encoding="utf-8") as f:
                    sessions.append(json.load(f))
        return sessions

    def update_task_output(self, session: Dict[str, Any], task_name: str, output: str) -> Dict[str, Any]:
        session["task_outputs"][task_name] = output
        session["completed_tasks"].append(task_name)
        session["current_task"] = None
        self.save_session(session)
        return session

    def append_artifact(self, session: Dict[str, Any], artifact: Dict[str, str]) -> Dict[str, Any]:
        session["artifacts"].append(artifact)
        self.save_session(session)
        return session
