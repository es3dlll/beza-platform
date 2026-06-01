# 17 - تطبيق React (React Implementation) - إدارة البطاقة

## هوك مخصص (Custom Hook): useCardManage

```javascript
import { useState } from "react";
import api from "../services/api";

export function useCardManage() {
  const [state, setState] = useState({ loading: false });

  const updateStatus = async (cardId, action) => {
    setState({ loading: true });
    try {
      const res = await api.patch(`/api/v1/cards/${cardId}/${action}`);
      setState({ loading: false, data: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.response?.data?.message || e.message });
    }
  };

  return { ...state, updateStatus };
}
```

## Manage Actions Component

```jsx
function CardActions({ cardId }) {
  const { loading, updateStatus } = useCardManage();

  return (
    <div className="card-actions">
      <button onClick={() => updateStatus(cardId, "freeze")} disabled={loading}>
        Freeze
      </button>
      <button onClick={() => updateStatus(cardId, "unfreeze")} disabled={loading}>
        Unfreeze
      </button>
      <button onClick={() => updateStatus(cardId, "cancel")} disabled={loading} className="danger">
        Cancel Card
      </button>
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/cards/{id}/freeze | PATCH | Freeze a card |
| /api/v1/cards/{id}/unfreeze | PATCH | Unfreeze a card |
| /api/v1/cards/{id}/cancel | PATCH | Cancel a card |
