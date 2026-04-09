# EventHub

Event booking app: users browse events and book tickets; administrators manage events and bookings.  
Frontend:Vue 3 (Router, Pinia, Axios, Bootstrap). 
Backend: PHP REST API (MVC-style, JWT, MariaDB).

URLs and ports (localhost)



In the browser go to: http://localhost:5173

## Login Credentials
Admin
    UserName: admin@eventhub.local
    Password: admin123
User
    UserName: user@eventhub.local 
    Password: user123 


phpMyAdmin: http://localhost:8080 
            user: developer 
            password: secret123


Then open **http://localhost:5173**. The dev server sends `/api` to **http://localhost:80** (your Docker API must be running).




Base URL: /events/{id}` — one event  
- `POST|PUT|DELETE /events` and `/events/{id}` — admin only
- `GET /bookings` — my bookings (admin: all + filters)  
- `POST /bookings` — create booking  
- `PUT /bookings/{id}` — status (admin)  
- `DELETE /bookings/{id}` — cancel / admin delete  
- `POST /bookings/{id}/pay` — pay pending booking  

Errors look like: `{ "error": "message" }` with an HTTP error code.


## Project folders

- `app/public/index.php` — API entry + routes  
- `app/src/` — PHP controllers, repositories, JWT, etc.  
- `database/init.sql` — tables + seed (runs on first database create only)  
- `frontend/` — Vue app  
