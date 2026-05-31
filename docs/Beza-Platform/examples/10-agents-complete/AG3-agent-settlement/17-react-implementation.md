# 17 - تطبيق React (React Implementation) - تسوية العمولات (Agent Settlement)

## هوك مخصص (Custom Hook): useSettlement

```javascript
import { useState } from "react";
import api from "../services/api";

export function useSettlement() {
  const [state, setState] = useState({ loading: false });

  const requestSettlement = async () => {
    setState({ loading: true });
    try {
      const res = await api.post("/api/v1/agent/settlement");
      setState({ loading: false, data: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.response?.data?.message || e.message });
    }
  };

  const getHistory = async () => {
    setState({ loading: true });
    try {
      const res = await api.get("/api/v1/agent/settlements");
      setState({ loading: false, history: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.message });
    }
  };

  return { ...state, requestSettlement, getHistory };
}
```

## Settlement Component

```jsx
function AgentSettlement() {
  const { loading, data, history, error, requestSettlement, getHistory } = useSettlement();

  return (
    <div className="settlement">
      <button onClick={requestSettlement} disabled={loading}>
        {loading ? "Processing..." : "Request Settlement"}
      </button>
      <button onClick={getHistory}>View History</button>

      {data && <div>Settlement: {data.amount} SYP — {data.status}</div>}
      {history && history.map(s => (
        <div key={s.id}>{s.date}: {s.amount} SYP - {s.status}</div>
      ))}
      {error && <div className="error">{error}</div>}
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/agent/settlement | POST | Request settlement |
| /api/v1/agent/settlements | GET | Settlement history |
