# 17 - تطبيق React (React Implementation) - لوحة تحكم الوكيل (Agent Dashboard)

## هوك مخصص (Custom Hook): useAgentDashboard

```javascript
import { useState, useEffect } from "react";
import api from "../services/api";

export function useAgentDashboard() {
  const [state, setState] = useState({ loading: true, stats: null });

  useEffect(() => {
    api.get("/api/v1/agent/dashboard")
      .then(res => setState({ loading: false, stats: res.data.data }))
      .catch(e => setState({ loading: false, error: e.message }));
  }, []);

  return state;
}
```

## Dashboard Component

```jsx
function AgentDashboard() {
  const { loading, stats, error } = useAgentDashboard();

  if (loading) return <div>Loading dashboard...</div>;
  if (error) return <div className="error">{error}</div>;

  return (
    <div className="dashboard">
      <div className="stat-card">
        <h4>Today's Transactions</h4>
        <p>{stats.today_count}</p>
      </div>
      <div className="stat-card">
        <h4>Today's Volume</h4>
        <p>{stats.today_volume} SYP</p>
      </div>
      <div className="stat-card">
        <h4>Commission Earned</h4>
        <p>{stats.commission} SYP</p>
      </div>
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/agent/dashboard | GET | Get agent dashboard stats |
