# Mini Support Ticketing System

Simple support ticket system built with plain PHP. No frameworks, just good old PHP with MVC pattern.

## What it does

- Users can create tickets and get help
- Admins can manage everything
- Agents can handle tickets
- File uploads for tickets
- Simple token auth
- Rate limiting to prevent spam

## Tech stuff

- PHP 8+ (no frameworks)
- SQLite database
- MVC structure
- JSON API responses
- File uploads
- Token authentication

## Requirements

- PHP 8+
- SQLite
- A web server or just use `php -S localhost:8000`

## Setup

1. Clone this repo
2. Run the database setup:

```bash
php database/migrate.php
php database/seed.php
```

3. Make uploads folder writable:

```bash
chmod 755 uploads/
```

4. Start the server:

```bash
php -S localhost:8000
```

That's it! Go to `http://localhost:8000/health` to check if it's working.

## How to use

### Login first

```bash
POST /auth/login
{
  "email": "admin@gmail.com",
  "password": "admin123"
}
```

### Then use the token

Add this header to your requests:

```
Authorization: Bearer <your-token>
```

## API Endpoints

### Auth stuff

- `POST /auth/register` - Sign up
- `POST /auth/login` - Login
- `POST /auth/logout` - Logout

### Users (admin only)

- `GET /users` - List users
- `POST /users` - Create user
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user

### Departments

- `GET /departments` - List departments
- `POST /departments` - Create (admin only)
- `PUT/DELETE /departments/{id}` - Update/Delete (admin only)

### Tickets

- `GET /tickets` - Your tickets (or all if admin/agent)
- `POST /tickets` - Create ticket
- `PUT /tickets/{id}` - Update ticket
- `POST /tickets/{id}/assign` - Assign to agent
- `PUT /tickets/{id}/status` - Change status

### Notes

- `GET /tickets/{id}/notes` - Get notes
- `POST /tickets/{id}/notes` - Add note
- `PUT/DELETE /tickets/{ticketId}/notes/{noteId}` - Update/Delete note

### File uploads

- `POST /tickets/{id}/attachments` - Upload file
- `GET /tickets/{id}/attachments/{attachmentId}/download` - Download file

## User Roles

**Admin** - Can do everything  
**Agent** - Handle tickets, add notes  
**User** - Create tickets, view own tickets

## Test Users

After running the seed script:

```
Admin: admin@gmail.com / admin123
Agent: john.agent@gmail.com / agent123
User: customer1@gmail.com / customer123
```

## Testing

Use Postman collection or curl:

```bash
# Check if working
curl http://localhost:8000/health

# Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@gmail.com", "password": "admin123"}'

# Get tickets (use token from login)
curl -X GET http://localhost:8000/tickets \
  -H "Authorization: Bearer <your-token>"
```

## Project Structure

```
├── index.php                 # Main file
├── config/database.php       # DB settings
├── controllers/              # Handle requests
├── models/                   # Database stuff
├── helpers/                  # Utility functions
├── routes/api.php           # All routes
├── database/                # DB files and scripts
└── uploads/                 # Uploaded files
```

## Issues?

- Database error? Run `php database/migrate.php`
- File upload not working? Check `uploads/` folder permissions
- Auth issues? Make sure you're sending the token correctly

That's pretty much it! It's a simple ticket system that works.
