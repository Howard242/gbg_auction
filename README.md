# GoBidGo (GBG) Online Auction System

GoBidGo (GBG) is a web-based online auction platform that allows users to buy and sell items through live and timed auctions. The system supports user authentication, seller approval, bidding management, post-auction processing, and an admin-controlled environment.

This project is developed as part of an academic and practical learning experience, focusing on web development, databases, and system security.

---

## 🚀 Features

### 👥 User Features
- User registration and login
- Upgrade from regular user to seller account
- Browse live and timed auctions
- Place bids on auction items (authenticated users only)
- Personalized auction recommendations
- Search and sort auctions (highest bid, latest, etc.)
- Forgot password functionality
- Floating chatbot for user assistance

### 🛒 Seller Features
- Seller registration with admin approval
- Add auction items (Live or Timed auctions)
- Manage listed items
- Relist expired auctions
- View auction performance and history

### ⏱ Auction Types
- **Live Auctions**
  - Fast-paced auctions with countdown timers
  - Items can move to timed auctions if unsold
- **Timed Auctions**
  - Auctions run for a predefined period
  - Winner announced automatically when time ends

### 🏆 Post-Auction Management
- Sold items moved to auction history
- Expired auctions handled separately
- Email notifications to buyers and sellers
- Payment and delivery scheduling
- Real estate visit scheduling (if applicable)

### 🛠 Admin Features
- Admin authentication
- Seller approval system
- Manage auctions and users
- Chatbot response management
- Secure admin panel (not publicly accessible)

---

## 🧠 Chatbot System
- Built using PHP
- Floating chat widget
- Keyword-based response matching
- NLP-inspired logic
- Learns new query variations
- Logs user queries for analysis
- Admin panel for managing chatbot responses

---

## 🧰 Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP
- **Other Tools:** AJAX, Bootstrap (optional)

---

## 🗂 Project Structure

```
gbg_auction/
│
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── templates/
│   ├── index.php
│   ├── login.php
│   └── register.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── includes/
│   ├── db.php
│   ├── auth.php
│   └── functions.php
│
├── auctions/
├── chatbot/
├── README.md
└── index.php
```

---

## 🗄 Database Overview

### Key Tables
- `users`
- `admins`
- `auctions`
- `bids`
- `chatbot_responses`
- `chatbot_logs`

### Auction Status Values
- `active`
- `sold`
- `expired`

---

## ⚙️ Installation & Setup

1. Install **XAMPP**
2. Copy the project into:
   ```
   htdocs/gbg_auction
   ```
3. Start **Apache** and **MySQL**
4. Import the database into phpMyAdmin
5. Update database credentials in:
   ```
   includes/db.php
   ```
6. Open in browser:
   ```
   http://localhost/gbg_auction
   ```

---

## 🔐 Security Notes
- Admin folder restricted from public access
- Password hashing implemented
- Authentication required for bidding and selling

---

## 📌 Future Improvements
- Mobile app integration
- Online payment gateway
- Advanced recommendation engine
- AI-powered chatbot enhancements

---

## 👨‍💻 Author

**Haward Mukoma**  
BSc. Information & Communication Technology  
Laikipia University  

📧 Email: howardmukoma242@gmail.com  
📞 Phone: 0713592840  

---

## 📄 License
This project is for educational purposes and non-commercial use.
