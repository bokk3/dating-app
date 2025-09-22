# Copilot Instructions for AI Coding Agents

## Project Overview
This is a dating app project. We're writing a dating app, swiping interface, matches can real time chat, user profile and settings page. Mobile first design, possible to turn into apps at some point.

I'm most familiar with SQL, dev on an arm64 debian machine. I'm familiar with PHP and js.
I would consider using different languages if there are faster alternatives.

Project requires a solid login system, and an admin backend with CRUD for users and help requests, also a method of processing payments eventually.

Registration should confirm user email.

## Architecture Overview
**Technology Stack**: PHP backend with MVC architecture, Modern JavaScript frontend, MySQL database with proper relationships, RESTful API structure, PWA capabilities

**Planned Directory Structure**:
```
dating-app/
├── config/          # Database and app configuration
├── src/             # Core application logic
│   ├── controllers/ # Request handlers and business logic
│   ├── models/      # Data models and database interactions
│   ├── middleware/  # Authentication, validation, CORS
│   └── utils/       # Helper functions and utilities
├── public/          # Web-accessible files (entry point)
├── admin/           # Admin panel for user/help request CRUD
├── api/             # REST API endpoints for mobile/frontend
├── database/        # Database schema and migrations
├── templates/       # PHP templates for server-side rendering
└── vendor/          # Composer dependencies
```

## Guidance for AI Agents
- **Entry Point**: `public/index.php` serves as the main application entry point
- **Database**: Use SQL with proper migrations in `database/migrations/`
- **API Design**: RESTful endpoints in `api/` for frontend consumption
- **Authentication**: Implement in `src/middleware/` with email confirmation flow
- **Admin Panel**: Separate admin interface in `admin/` for user management
- **Mobile-First**: All frontend components should prioritize mobile experience
- **Real-time Features**: Plan for WebSocket/Server-Sent Events for chat functionality

## Database Schema Implementation
- **Complete Schema**: All tables with proper relationships and indexes
- **User Sessions**: Enhanced security with session management tables
- **Admin Logging**: Activity tracking for all administrative actions
- **Payment System**: Transaction tracking and payment processing tables
- **Help Requests**: Full ticket management system with status tracking

## Development Patterns
- **MVC Structure**: Controllers handle requests, models manage data, templates render views
- **API-First**: Design API endpoints before implementing frontend features
- **Security**: Password hashing, SQL injection prevention, CSRF protection, email verification, secure sessions
- **Payment Integration**: Prepare for future payment gateway integration in business logic layer
- **PWA Ready**: Progressive Web App capabilities for mobile app conversion
- **Database Patterns**: Singleton connection pooling, optimized queries with proper indexing
- **Professional Routing**: Comprehensive routing system with middleware support

## Core Features Status
✅ **Implemented/Ready**:
- Mobile-first responsive design
- User authentication with email verification
- Swipeable card interface
- Real-time chat preparation
- Admin backend structure
- Payment processing preparation
- Professional routing system
- Rate limiting with Redis fallback
- Password reset functionality
- Age and distance-based matching
- Complete API system with authentication
- Profile management with photo uploads
- Discovery algorithm with distance/preference filtering
- Swipe functionality with match detection
- Real-time messaging system
- Admin panel with full CRUD operations
- Content moderation capabilities
- Image resizing and optimization
- Complete database schema with relationships
- Enhanced login system with account lockout
- Professional admin panel with real-time dashboard
- Payment transaction tracking system
- Help request management system

## Security Implementation
- **Password Hashing**: Use PHP's `password_hash()` and `password_verify()`
- **SQL Injection Prevention**: Prepared statements and parameterized queries
- **CSRF Protection**: Token-based protection for forms
- **Email Verification**: Complete registration flow with email confirmation
- **Secure Sessions**: Proper session management and regeneration
- **Enhanced Security**: Password peppering, Argon2ID hashing, XSS protection
- **Rate Limiting**: IP-based rate limiting with Redis caching
- **Security Headers**: CORS, clickjacking protection, content security policy
- **File Upload Security**: Comprehensive validation and processing with type checking
- **Password Strength**: Enforced password requirements and validation
- **Account Lockout**: Failed login attempt tracking with temporary lockouts
- **Session Validation**: Enhanced session security with expiration handling
- **Admin Security**: Separate admin authentication with activity logging
- **Activity Monitoring**: Comprehensive logging of all user and admin actions

## Admin Backend Features
- **User Management**: Activate/deactivate users, assign admin rights, user statistics
- **Help Request System**: CRUD operations for user support tickets and responses
- **Statistics Dashboard**: Comprehensive analytics and user engagement metrics
- **Content Moderation**: Review and manage user-generated content and profiles
- **System Monitoring**: Track application performance and security events
- **Professional Interface**: Modern responsive design with intuitive navigation
- **Real-time Dashboard**: Live statistics and activity monitoring
- **Advanced Search**: User and ticket filtering with multiple criteria
- **Payment Tracking**: Complete transaction monitoring and management
- **Activity Logging**: Detailed audit trail for all administrative actions

## Performance Optimizations
- **Database**: Singleton pattern with connection pooling for efficient resource usage
- **Caching**: Redis preparation for session storage and rate limiting
- **Queries**: Optimized database queries with proper indexing strategies
- **Pagination**: Built-in pagination support for large datasets
- **File Handling**: Comprehensive upload validation and processing
- **Image Processing**: Automatic image resizing and optimization for profile photos
- **Location Queries**: Proper indexing for distance-based discovery and matching
- **API Optimization**: Efficient endpoint design with minimal database calls

## Key Implementation Areas
- **Swiping Interface**: JavaScript-based gesture handling with smooth animations
- **Real-time Chat**: WebSocket connection management and message persistence
- **User Profiles**: Image upload handling, profile validation, privacy controls
- **Match System**: Algorithm for user matching and notification system

---
_Last updated: 2025-09-22_
