# Suraxa Admin Neo

A modern administrative dashboard for managing users, content, and reports for the Suraxa platform.

## Quick Start

```bash
# Clone the repository
git clone [repository-url] Suraxaneo/suraxa_admin_neo

# Navigate to project directory
cd Suraxaneo/suraxa_admin_neo

# Start XAMPP services
# Open XAMPP Control Panel and start Apache & MySQL

# Access the application
http://localhost/Suraxaneo/suraxa_admin_neo/
```

## Table of Contents
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Directory Structure](#directory-structure)
- [Code Description](#code-description)
- [Usage](#usage)
- [Security](#security)

## Features

### User Management
- **User Administration**
  - Add new users (`add-user.php`)
  - Manage existing users (`manage-users.php`)
  - Reset user passwords
  - Bulk user import functionality (`bulk-upload.php`)
  
### Library Management
- **Content Organization**
  - Create and manage content (files and folders)
  - Topic management
  - Content-Topic linking system

### Dashboard
- Program dashboard
- Detailed analytics

### Reports
- Summary reports
- User activity reports

### Admin & Company Settings
- Company management
- Admin password reset (`admin-reset.php`)
- Help section (`help.php`)

## Technology Stack

### Backend
- PHP
- MySQL
- Apache

### Frontend
- HTML5
- CSS3 with Bootstrap
- JavaScript
- Bootstrap Icons

### Development Tools
- Git for version control
- XAMPP for local development

## System Requirements

### Server Requirements
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache 2.4 or higher

### Client Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 1024x768

## Installation

1. **Prerequisites**
   - Install [XAMPP](https://www.apachefriends.org/download.html)
   - Ensure Git is installed on your system

2. **Clone the Repository**
   ```bash
   cd C:\xampp\htdocs
   git clone [repository-url] Suraxaneo/suraxa_admin_neo
   ```

3. **Configure XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services
   - Verify services are running (green status)

4. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `suraxa_admin_neo`
   - Import the database schema from `database/suraxa_admin_neo.sql`
   - Configure database credentials in `includes/db_connect.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'suraxa_admin_neo');
     ```

5. **Access the Application**
   ```
   http://localhost/Suraxaneo/suraxa_admin_neo/
   ```

## Directory Structure

```
suraxa_admin_neo/
├── assets/
│   └── images/
│       ├── suraxa-logo.png
│       └── antiz-logo.png
├── components/
│   ├── sidebar.html
│   └── header.html
├── css/
├── js/
│   └── components.js
├── includes/
│   ├── db_connect.php      # Database connection configuration
│   ├── auth.php           # Authentication and authorization functions
│   └── user-validation-utils.php  # User input validation utilities
├── add-user.php
├── admin-reset.php
├── bulk-upload.php
├── edit-user.php
├── help.php
├── index.html
├── manage-users.php
└── README.md
```

## Code Description

### Core Components

#### 1. Database Connection
```php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'prabhu@antiz');
define('DB_NAME', 'suraxa_schneider_fs');

// Connection check
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection error. Please contact the administrator.");
}
```

#### 2. Authentication
```php
// Session check
if (!isset($_SESSION["userID"]) || empty($_SESSION["userID"])) {
    error_log("Authentication failed: No session ID found");
    return false;
}

// User verification
$stmt = $conn->prepare("SELECT UserID, CompanyID FROM user WHERE UserID = ?");
$stmt->bind_param("s", $userId);
```

#### 3. User Validation
```php
// Email existence check
function checkExistingEmails($loginIds, $conn) {
    $escapedIds = array_map(function($id) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $id) . "'";
    }, $loginIds);
    
    $sql = "SELECT LoginID FROM user WHERE LoginID IN (" . implode(',', $escapedIds) . ")";
}
```

### Security Implementations

#### 1. Session Management
```php
// Session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Development configuration
define('DEV_MODE', true);
define('DEV_USER_ID', 'U0005');
```

#### 2. Error Handling
```php
// Error logging examples
error_log("Database connection failed: " . $conn->connect_error);
error_log("Authentication failed: No session ID found");
error_log("Authentication failed: Prepare statement failed - " . $conn->error);
```

## Usage

### Core Files

#### 1. `includes/db_connect.php`
- **Purpose**: Database connection management
- **Usage**:
  ```php
  require_once 'includes/db_connect.php';
  $conn = getConnection();
  ```
- **Key Features**:
  - Connection pooling
  - Error handling
  - UTF-8 charset support

#### 2. `includes/auth.php`
- **Purpose**: Authentication and session management
- **Usage**:
  ```php
  require_once 'includes/auth.php';
  requireAuth(); // Enforce authentication
  ```
- **Key Features**:
  - Session validation
  - User verification
  - Development mode support

#### 3. `includes/user-validation-utils.php`
- **Purpose**: User input validation
- **Usage**:
  ```php
  require_once 'includes/user-validation-utils.php';
  $result = checkExistingEmails($email, $conn);
  ```
- **Key Features**:
  - Email validation
  - SQL injection prevention
  - Batch validation support
,
### User Management

#### 1. `add-user.php`
- **Purpose**: Add new users to the system
- **Access**: Admin only
- **Features**:
  - User registration
  - Role assignment
  - Email validation

#### 2. `manage-users.php`
- **Purpose**: View and manage existing users
- **Access**: Admin only
- **Features**:
  - User listing
  - Status updates
  - Bulk actions

#### 3. `edit-user.php`
- **Purpose**: Modify user information
- **Access**: Admin only
- **Features**:
  - Profile updates
  - Password reset
  - Role modification

### Administrative Functions

#### 1. `admin-reset.php`
- **Purpose**: Admin password recovery
- **Access**: 
- **Features**:
  - Secure reset process
  - Email verification
  - Temporary access

#### 2. `bulk-upload.php`
- **Purpose**: Mass user import
- **Access**: Admin only
- **Features**:
  - CSV file upload
  - Data validation
  - Error reporting

#### 3. `help.php`
- **Purpose**: System documentation
- **Access**: All users
- **Features**:
  - Usage guides
  - FAQ section
  - Support contact

### Frontend Components

#### 1. `components/sidebar.html`
- **Purpose**: Navigation menu
- **Usage**: Included in all pages
- **Features**:
  - Dynamic loading
  - Role-based menu items
  - Active state tracking

#### 2. `components/header.html`
- **Purpose**: Page header
- **Usage**: Included in all pages
- **Features**:
  - User info display
  - Quick actions
  - Notifications

### Assets

#### 1. `assets/images/`
- **Purpose**: Image storage
- **Files**:
  - `suraxa-logo.png`: Main logo
  - `antiz-logo.png`: Company logo
- **Usage**: Referenced in HTML/CSS

### JavaScript

#### 1. `js/components.js`
- **Purpose**: Frontend functionality
- **Features**:
  - Component loading
  - Form validation
  - AJAX requests

## Security

### Implemented Security Measures
- Password hashing
- Session management
- Input validation
- CSRF protection
- Secure file upload handling
- XSS prevention
- SQL injection prevention

### Security Best Practices
- All passwords are securely hashed
- Input validation and sanitization
- Secure session handling
- Protected admin routes
- File upload restrictions
- Regular security updates

## API Documentation

### Authentication
```php
// Example API endpoint
POST /api/auth/login
{
    "username": "string",
    "password": "string"
}
```

### User Management
```php
// Get user list
GET /api/users

// Create user
POST /api/users
{
    "username": "string",
    "email": "string",
    "password": "string",
    "role": "string"
}
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify MySQL service is running
   - Check database credentials in `includes/db_connect.php`
   - Ensure database `suraxa_admin_neo` exists

2. **File Upload Issues**
   - Check file size limits
   - Verify file permissions
   - Ensure correct file types

3. **Login Problems**
   - Clear browser cache
   - Check session configuration
   - Verify user credentials

### Error Logs
- Apache error log: `C:\xampp\apache\logs\error.log`
- PHP error log: `C:\xampp\php\logs\php_error.log`

