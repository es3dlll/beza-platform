# 17 - تطبيق React (React Implementation) - خريطة الوكلاء (Agent Map)

## هوك مخصص (Custom Hook): useAgentLocation

```javascript
import { useState, useEffect, useCallback } from "react";
import api from "../services/api";

export function useAgentLocation() {
  const [state, setState] = useState({ loading: false, location: null });

  const updateLocation = useCallback(async (lat, lng) => {
    try {
      await api.post("/api/v1/agent/location", { latitude: lat, longitude: lng });
    } catch (e) {
      console.error("Location update failed", e);
    }
  }, []);

  const getNearbyAgents = async (lat, lng, radius = 5) => {
    setState({ loading: true });
    try {
      const res = await api.get(`/api/v1/agents/nearby?lat=${lat}&lng=${lng}&radius=${radius}`);
      setState({ loading: false, agents: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.message });
    }
  };

  return { ...state, updateLocation, getNearbyAgents };
}
```

## Map Component

```jsx
function AgentMap() {
  const { agents, loading, updateLocation, getNearbyAgents } = useAgentLocation();

  useEffect(() => {
    navigator.geolocation.getCurrentPosition(pos => {
      updateLocation(pos.coords.latitude, pos.coords.longitude);
      getNearbyAgents(pos.coords.latitude, pos.coords.longitude);
    });
  }, []);

  return (
    <div className="agent-map">
      {loading && <div>Loading nearby agents...</div>}
      {agents && agents.map(a => (
        <div key={a.id} className="agent-marker">
          {a.name} — {a.distance_km}km away
        </div>
      ))}
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/agent/location | POST | Update agent location |
| /api/v1/agents/nearby | GET | Find nearby agents |
