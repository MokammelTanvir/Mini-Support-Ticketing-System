# Mini Support Ticketing System

A comprehensive RESTful API-based support ticketing system built with vanilla PHP using Object-Oriented Programming and MVC architecture.

## 🚀 Features

### ✅ Core Features

- **User Management**: Registration, authentication, and role-based access control (Admin, Agent, User)
- **Department Management**: Create and manage support departments
- **Ticket Management**: Full CRUD operations for support tickets with status tracking
- **Ticket Notes**: Add, update, and delete notes on tickets
- **File Attachments**: Upload, download, and manage file attachments for tickets
- **Token-Based Authentication**: Secure JWT-like token authentication
- **Rate Limiting**: Protection against abuse with configurable limits
- **Role-Based Access Control**: Different permissions for Admin, Agent, and User roles

### 🔧 Technical Features

- **Pure PHP**: No frameworks, built with vanilla PHP 8+
- **MVC Architecture**: Clean separation of concerns
- **SQLite Database**: Lightweight, portable database
- **Manual Routing**: Custom routing system with regex pattern matching
- **JSON API**: RESTful API with consistent JSON responses
- **File Security**: Secure file upload with type validation and access control
- **Error Handling**: Comprehensive error handling and logging

## 📋 Requirements

- PHP 8.0 or higher
- SQLite3 extension
- cURL extension (for testing)
- Web server (Apache/Nginx) or PHP built-in server

## 🛠️ Installation

1. **Clone the repository**

```bash
git clone <repository-url>
cd Mini-Support-Ticketing-System
```

2. **Set up the database**

```bash
php database/migrate.php
php database/seed.php
```

3. **Set permissions**

```bash
chmod 755 uploads/
chmod 644 uploads/.htaccess
```

4. **Start the server**

```bash
# Using PHP built-in server
php -S localhost:8000

# Or configure your web server to point to the project directory
```

## 🗃️ Database Schema

The system uses SQLite with the following tables:

- `users` - User accounts and authentication
- `departments` - Support departments
- `tickets` - Support tickets
- `ticket_notes` - Notes/comments on tickets
- `ticket_attachments` - File attachments for tickets

## 🔐 Authentication

The API uses token-based authentication:

1. **Login** to get a token:

```bash
POST /auth/login
{
  "email": "admin@gmail.com",
  "password": "admin123"
}
```

2. **Include token** in subsequent requests:

```bash
Authorization: Bearer <your-token>
```

## 📚 API Documentation

### Base URL

```
http://localhost:8000
```

### Authentication Endpoints

- `POST /auth/register` - Register new user
- `POST /auth/login` - Login and get token
- `POST /auth/logout` - Logout and invalidate token

### User Management (Admin only)

- `GET /users` - Get all users
- `GET /users/{id}` - Get user by ID
- `POST /users` - Create user
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user

### Department Management

- `GET /departments` - Get all departments (authenticated users)
- `POST /departments` - Create department (admin only)
- `PUT /departments/{id}` - Update department (admin only)
- `DELETE /departments/{id}` - Delete department (admin only)

### Ticket Management

- `GET /tickets` - Get tickets (role-based filtering)
- `GET /tickets/{id}` - Get specific ticket
- `POST /tickets` - Create ticket
- `PUT /tickets/{id}` - Update ticket
- `DELETE /tickets/{id}` - Delete ticket (admin only)
- `POST /tickets/{id}/assign` - Assign ticket to agent
- `PUT /tickets/{id}/status` - Change ticket status
- `GET /tickets/stats/summary` - Get ticket statistics (admin/agent)
- `GET /tickets/assigned/me` - Get my assigned tickets (agent)

### Ticket Notes

- `GET /tickets/{id}/notes` - Get ticket notes
- `POST /tickets/{id}/notes` - Add note to ticket
- `PUT /tickets/{ticketId}/notes/{noteId}` - Update note
- `DELETE /tickets/{ticketId}/notes/{noteId}` - Delete note

### File Attachments

- `POST /tickets/{id}/attachments` - Upload files (multipart/form-data)
- `GET /tickets/{id}/attachments` - Get ticket attachments
- `GET /tickets/{id}/attachments/{attachmentId}/download` - Download file
- `DELETE /tickets/{id}/attachments/{attachmentId}` - Delete attachment

### Admin Endpoints

- `GET /admin/storage/stats` - Get file storage statistics

## 🔒 Security Features

### Rate Limiting

- **Login attempts**: 10 per hour per IP
- **Ticket creation**: 5 per hour per user
- **Note creation**: 15 per hour per user
- **File uploads**: 5 per hour per user

### File Upload Security

- **File type validation**: Only allowed file types (images, documents, archives)
- **File size limit**: 10MB maximum per file
- **Secure storage**: Files stored outside web root with unique names
- **Access control**: Only authenticated users can access their own files

### Authentication Security

- **Token-based authentication**: Secure JWT-like tokens
- **Password hashing**: Bcrypt password hashing
- **Role-based access**: Admin, Agent, User roles with different permissions

## 👥 User Roles

### Admin

- Full access to all features
- User management
- Department management
- All ticket operations
- System statistics

### Agent

- View and manage assigned tickets
- Add notes to tickets
- Change ticket status
- Assign tickets to other agents

### User

- Create tickets
- View own tickets
- Add notes to own tickets
- Upload files to own tickets

## 🧪 Testing

### Sample Users (after running seed)

```
Admin: admin@gmail.com / admin123
Agent: john.agent@gmail.com / agent123
Agent: sarah.agent@gmail.com / agent123
User: customer1@gmail.com / customer123
User: customer2@gmail.com / customer123
```

### Using Postman

Import the `postman_collection.json` file for complete API testing.

### Manual Testing

```bash
# Test health check
curl http://localhost:8000/health

# Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@gmail.com", "password": "admin123"}'

# Get tickets (use token from login)
curl -X GET http://localhost:8000/tickets \
  -H "Authorization: Bearer <your-token>"
```

## 📁 Project Structure

```
├── index.php                 # Front controller
├── .htaccess                 # URL rewriting
├── README.md                 # This file
├── postman_collection.json   # Postman API collection
├── config/
│   └── database.php          # Database configuration
├── controllers/              # API controllers
│   ├── AuthController.php
│   ├── UserController.php
│   ├── DepartmentController.php
│   ├── TicketController.php
│   ├── TicketNoteController.php
│   └── AttachmentController.php
├── models/                   # Data models
│   ├── User.php
│   ├── Department.php
│   ├── Ticket.php
│   ├── TicketNote.php
│   └── TicketAttachment.php
├── helpers/                  # Utility classes
│   ├── Response.php
│   ├── Auth.php
│   └── RateLimit.php
├── routes/
│   └── api.php              # API routes definition
├── database/
│   ├── schema.sql           # Database schema
│   ├── migrate.php          # Migration script
│   ├── seed.php             # Sample data seeder
│   └── ticketing_system.db  # SQLite database
└── uploads/                 # File uploads directory
    └── .htaccess           # Security for uploads
```

## 🔧 Configuration

### Rate Limits

Edit the rate limits in respective controllers:

- `AuthController.php` - Login attempts
- `TicketController.php` - Ticket creation
- `TicketNoteController.php` - Note creation
- `AttachmentController.php` - File uploads

### File Upload Settings

Edit `AttachmentController.php`:

- `$maxFileSize` - Maximum file size (default: 10MB)
- `$allowedTypes` - Allowed MIME types

## 🐛 Troubleshooting

### Common Issues

1. **Database not found**

   - Run `php database/migrate.php`
   - Check file permissions

2. **File upload fails**

   - Check `uploads/` directory permissions
   - Verify PHP upload settings

3. **Authentication issues**
   - Check token validity
   - Verify user credentials

### Error Logs

Check PHP error logs and the API returns detailed error messages in JSON format.

## 🚀 Deployment

### Production Considerations

1. **Security**: Use HTTPS in production
2. **Database**: Consider MySQL/PostgreSQL for production
3. **File Storage**: Use cloud storage for scalability
4. **Caching**: Implement caching for better performance
5. **Monitoring**: Add logging and monitoring

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions, please create an issue in the repository.

---

**Built with ❤️ using vanilla PHP and modern web development practices.**
