# Unity ↔ TowerDefenseHQ Bridge

## Overview

The `game/unity/index.php` page exposes JavaScript functions that your C# code
can call via a `.jslib` plugin. The bridge lets Unity:

- Upload scores to the leaderboard
- Read the logged-in user's data
- Receive unlockables from the backend

---

## 1. Upload a Score from C#

**JavaScript side** (`index.php` already contains this):
```js
window.UploadScore = function(csvData) { ... }
// csvData format: "score,waves,timeSecs,mapId"
// e.g.  "12500,8,180,1"
```

**C# side** — create `Assets/Plugins/Bridge.jslib`:
```js
mergeInto(LibraryManager.library, {
    UploadScore: function(strPtr) {
        var str = UTF8ToString(strPtr);
        window.UploadScore(str);
    }
});
```

**C# caller**:
```csharp
using System.Runtime.InteropServices;

public class ScoreBridge : MonoBehaviour
{
    [DllImport("__Internal")]
    private static extern void UploadScore(string csvData);

    public void SendScore(int score, int waves, int timeSecs, int mapId = 1)
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        UploadScore($"{score},{waves},{timeSecs},{mapId}");
#endif
    }
}
```

---

## 2. Get Logged-In User Data

**JavaScript side** (already in `index.php`):
```js
window.GetUserInfo = function() { ... }
// Calls /api/get_user_data.php
// Then does: unityInstance.SendMessage("Bridge", "OnUserData", jsonString)
```

**C# side** — create a GameObject named **`Bridge`** with this script:
```csharp
public class BridgeReceiver : MonoBehaviour
{
    // Called by JS: unityInstance.SendMessage("Bridge", "OnUserData", json)
    public void OnUserData(string json)
    {
        var data = JsonUtility.FromJson<UserData>(json);
        Debug.Log("Logged in as: " + data.username);
    }

    [System.Serializable]
    public class UserData
    {
        public int    id;
        public string username;
        public string email;
        public string role;
    }
}
```

**jslib** to trigger the fetch from C#:
```js
mergeInto(LibraryManager.library, {
    GetUserInfo: function() { window.GetUserInfo(); }
});
```

---

## 3. API Endpoints Available to Unity

| Endpoint | Method | Description |
|---|---|---|
| `/api/login.php` | POST | `{"email":"…","password":"…"}` |
| `/api/register.php` | POST | `{"username":"…","email":"…","password":"…"}` |
| `/api/upload_score.php` | POST | `{"score":N,"waves_survived":N,"time_survived":N,"map_id":N}` |
| `/api/get_scores.php` | GET | `?type=score&limit=50&map_id=1` |
| `/api/get_user_data.php` | GET | Returns current session user |
| `/api/get_towers.php` | GET | All tower definitions |
| `/api/get_enemies.php` | GET | All enemy definitions |
| `/api/get_maps.php` | GET | All map definitions |
| `/api/get_unlockables.php` | GET | User's unlocked items |

All endpoints return JSON: `{"success": true/false, "data": [...] }`

---

## 4. Deployment Checklist

- [ ] Unity build output copied to `game/unity/Build/`
- [ ] Filenames updated in `game/unity/index.php` (`$build_loader` etc.)
- [ ] Compression format matches `.htaccess` (Gzip or Brotli)
- [ ] `game/unity/index.php` linked in nav (add to `includes/nav.php`)
- [ ] Test at `http://localhost/Proftaak/game/unity/`
