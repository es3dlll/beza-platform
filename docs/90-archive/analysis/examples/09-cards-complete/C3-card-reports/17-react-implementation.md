# 17 - تطبيق React (React Implementation) - تقارير البطاقة

## هوك مخصص (Custom Hook): useCardReports

```javascript
import { useState, useEffect } from "react";
import api from "../services/api";

export function useCardReports(cardId) {
  const [state, setState] = useState({ loading: true, reports: [] });

  useEffect(() => {
    if (!cardId) return;
    setState({ loading: true });
    api.get(`/api/v1/cards/${cardId}/reports`)
      .then(res => setState({ loading: false, reports: res.data.data }))
      .catch(e => setState({ loading: false, error: e.message }));
  }, [cardId]);

  return state;
}
```

## Reports Component

```jsx
function CardReports({ cardId }) {
  const { loading, reports, error } = useCardReports(cardId);

  if (loading) return <div>Loading reports...</div>;
  if (error) return <div className="error">{error}</div>;

  return (
    <div className="reports">
      <h3>Card Activity</h3>
      <table>
        <thead>
          <tr><th>Date</th><th>Type</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
          {reports.map(r => (
            <tr key={r.id}>
              <td>{r.created_at}</td>
              <td>{r.type}</td>
              <td>{r.amount}</td>
              <td>{r.status}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/cards/{id}/reports | GET | Get card reports |
