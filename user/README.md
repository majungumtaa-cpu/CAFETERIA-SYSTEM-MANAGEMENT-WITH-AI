# Cafeteria Management System - User Pages

This directory contains the user-facing pages for customers to interact with the cafeteria management system.

## Files Created

### Authentication
- `user_login.php` - Customer login page using phone number and full name
- `user_register.php` - Customer registration page
- `user_logout.php` - Customer logout functionality
- `index.php` - Redirects to login or dashboard based on session status

### Main User Interface
- `user_dashboard.php` - Main dashboard showing customer's options and recent activity

### Core Functionality
- `menu.php` - Browse menu, add items to cart, view products by category
- `cart.php` - View shopping cart, update quantities, remove items
- `checkout.php` - Complete order placement with employee selection
- `reservations.php` - Make service reservations (tables, events, etc.)
- `events.php` - View and register for upcoming events
- `feedback.php` - Submit customer feedback and ratings

## Features Implemented

### Customer Authentication
- Simple login using phone number and full name
- Registration for new customers
- Session-based authentication
- Automatic logout functionality

### Menu & Ordering System
- Browse products by category
- Add items to shopping cart
- Update cart quantities
- Remove items from cart
- Complete checkout process
- Stock validation during ordering
- Order assignment to employees

### Reservations
- View available services
- Make reservations for future dates
- View existing reservations
- Service information display

### Events
- Browse upcoming events
- Register for events
- View registration status
- Track registered events

### Feedback System
- Submit star ratings (1-5)
- Optional comments
- One-time feedback per customer
- View previous feedback

## Database Integration

The user pages integrate with the existing database schema:
- `customer` table for user accounts
- `product` and `menu_category` for menu items
- `order` and `order_detail` for order management
- `reservation` for service bookings
- `event` and `event_customer` for event registrations
- `feedback` for customer ratings
- `employee` for order assignment

## Security Features

- Prepared statements for all database queries
- Input validation and sanitization
- Session-based authentication
- CSRF protection through POST requests
- SQL injection prevention

## User Experience

- Responsive design with modern UI
- Intuitive navigation between pages
- Clear success/error messaging
- Mobile-friendly interface
- Consistent styling with main application

## Usage

1. Customers register or login using their phone number and full name
2. Access the dashboard to see available options
3. Browse menu and place orders
4. Make reservations for services
5. Register for events
6. Submit feedback

## Integration with Admin System

The user pages work alongside the existing admin and employee interfaces:
- Orders placed by customers are visible to employees and admins
- Customer data is shared across all interfaces
- Feedback and reservations are accessible through admin panels
- Stock levels are updated automatically when orders are placed