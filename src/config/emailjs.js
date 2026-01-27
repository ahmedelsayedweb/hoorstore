// EmailJS Configuration
// Get these values from https://www.emailjs.com/
// 1. Create an account at emailjs.com
// 2. Add an Email Service (Gmail, Outlook, etc.)
// 3. Create an Email Template
// 4. Get your Public Key from Account > API Keys

export const EMAILJS_CONFIG = {
  SERVICE_ID: 'YOUR_SERVICE_ID',      // Replace with your EmailJS service ID
  TEMPLATE_ID: 'YOUR_TEMPLATE_ID',    // Replace with your EmailJS template ID
  PUBLIC_KEY: 'YOUR_PUBLIC_KEY'       // Replace with your EmailJS public key
}

// Email template variables (use these in your EmailJS template):
// {{order_number}} - Order number
// {{customer_name}} - Customer full name
// {{customer_email}} - Customer email/contact
// {{customer_phone}} - Customer phone
// {{customer_address}} - Full delivery address
// {{order_items}} - HTML table of order items
// {{subtotal}} - Order subtotal
// {{shipping}} - Shipping cost
// {{total}} - Total amount
// {{order_date}} - Order date
