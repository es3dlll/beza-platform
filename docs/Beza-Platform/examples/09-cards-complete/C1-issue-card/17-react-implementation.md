# 17 - تطبيق React (React Implementation) - إصدار البطاقة

## هوك مخصص (Custom Hook): useIssueCard

```javascript
import { useState } from "react";
import api from "../services/api";

export function useIssueCard() {
  const [state, setState] = useState({ loading: false, card: null });

  const issue = async (payload) => {
    setState({ loading: true });
    try {
      const res = await api.post("/api/v1/cards/issue", payload);
      setState({ loading: false, card: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.response?.data?.message || e.message });
    }
  };

  return { ...state, issue };
}
```

## Form Component

```jsx
function IssueCardForm() {
  const { loading, card, error, issue } = useIssueCard();
  const [form, setForm] = useState({ card_type: "virtual" });

  const handleSubmit = (e) => {
    e.preventDefault();
    issue(form);
  };

  return (
    <form onSubmit={handleSubmit}>
      <select value={form.card_type} onChange={e => setForm({...form, card_type: e.target.value})}>
        <option value="virtual">Virtual Card</option>
        <option value="physical">Physical Card</option>
      </select>
      <button type="submit" disabled={loading}>
        {loading ? "Issuing..." : "Issue Card"}
      </button>
      {card && <div>Card issued: ****{card.last_four}</div>}
      {error && <div className="error">{error}</div>}
    </form>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/cards/issue | POST | Issue a new card |
