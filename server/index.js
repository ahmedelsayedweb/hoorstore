import 'dotenv/config'
import express from 'express'
import cors from 'cors'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import nodemailer from 'nodemailer'
import multer from 'multer'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const app = express()
const PORT = 3001

// Uploads directory
const uploadsDir = path.join(__dirname, '../uploads')
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true })
}

// Multer configuration for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadsDir)
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9)
    const ext = path.extname(file.originalname)
    cb(null, uniqueSuffix + ext)
  }
})

const upload = multer({
  storage,
  limits: { fileSize: 5 * 1024 * 1024 }, // 5MB limit
  fileFilter: (req, file, cb) => {
    const allowedTypes = /jpeg|jpg|png|gif|webp/
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase())
    const mimetype = allowedTypes.test(file.mimetype)
    if (extname && mimetype) {
      cb(null, true)
    } else {
      cb(new Error('Only image files are allowed'))
    }
  }
})

// Middleware
app.use(cors())
app.use(express.json())
app.use('/uploads', express.static(uploadsDir))

// Serve dist folder in production
const distPath = path.join(__dirname, '../dist')
if (fs.existsSync(distPath)) {
  app.use(express.static(distPath))
}

// Database file paths
const productsDbPath = path.join(__dirname, '../db/products.json')
const cartDbPath = path.join(__dirname, '../db/cart.json')
const ordersDbPath = path.join(__dirname, '../db/orders.json')

// Email transporter (configure with your email settings)
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: process.env.EMAIL_USER || 'your-email@gmail.com',
    pass: process.env.EMAIL_PASS || 'your-app-password'
  }
})

// Helper functions for Orders
function readOrdersDB() {
  try {
    const data = fs.readFileSync(ordersDbPath, 'utf-8')
    return JSON.parse(data)
  } catch {
    return { orders: [] }
  }
}

function writeOrdersDB(data) {
  fs.writeFileSync(ordersDbPath, JSON.stringify(data, null, 2))
}

// Helper functions for Products
function readProductsDB() {
  const data = fs.readFileSync(productsDbPath, 'utf-8')
  return JSON.parse(data)
}

function writeProductsDB(data) {
  fs.writeFileSync(productsDbPath, JSON.stringify(data, null, 2))
}

// Helper functions for Cart
function readCartDB() {
  const data = fs.readFileSync(cartDbPath, 'utf-8')
  return JSON.parse(data)
}

function writeCartDB(data) {
  fs.writeFileSync(cartDbPath, JSON.stringify(data, null, 2))
}

// ============ Upload API ============

// POST - Upload image
app.post('/api/upload', upload.single('image'), (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' })
    }
    const url = `/uploads/${req.file.filename}`
    res.json({ url })
  } catch (error) {
    res.status(500).json({ error: 'Failed to upload file' })
  }
})

// ============ Products API ============

// GET all products
app.get('/api/products', (req, res) => {
  try {
    const db = readProductsDB()
    res.json(db.products)
  } catch (error) {
    res.status(500).json({ error: 'Failed to read products' })
  }
})

// GET single product by ID
app.get('/api/products/:id', (req, res) => {
  try {
    const db = readProductsDB()
    const product = db.products.find(p => p.id === parseInt(req.params.id))
    if (!product) {
      return res.status(404).json({ error: 'Product not found' })
    }
    res.json(product)
  } catch (error) {
    res.status(500).json({ error: 'Failed to read product' })
  }
})

// POST - Add new product
app.post('/api/products', (req, res) => {
  try {
    const db = readProductsDB()
    const newProduct = {
      id: db.products.length > 0 ? Math.max(...db.products.map(p => p.id)) + 1 : 1,
      name: req.body.name,
      price: req.body.price,
      salePrice: req.body.salePrice || null,
      category: req.body.category,
      image: req.body.image || '/images/products/default.jpg',
      description: req.body.description || '',
      inStock: req.body.inStock !== false,
      createdAt: new Date().toISOString()
    }
    db.products.push(newProduct)
    writeProductsDB(db)
    res.status(201).json(newProduct)
  } catch (error) {
    res.status(500).json({ error: 'Failed to add product' })
  }
})

// PUT - Update product
app.put('/api/products/:id', (req, res) => {
  try {
    const db = readProductsDB()
    const index = db.products.findIndex(p => p.id === parseInt(req.params.id))
    if (index === -1) {
      return res.status(404).json({ error: 'Product not found' })
    }

    const oldProduct = db.products[index]

    // Delete old main image if a new one is provided
    if (req.body.image && req.body.image !== oldProduct.image) {
      deleteImageFile(oldProduct.image)
    }

    // Delete removed album images
    if (req.body.images && Array.isArray(req.body.images) && oldProduct.images) {
      const newImages = req.body.images
      oldProduct.images.forEach(oldImg => {
        if (!newImages.includes(oldImg)) {
          deleteImageFile(oldImg)
        }
      })
    }

    db.products[index] = {
      ...db.products[index],
      ...req.body,
      id: parseInt(req.params.id) // Prevent ID change
    }
    writeProductsDB(db)
    res.json(db.products[index])
  } catch (error) {
    res.status(500).json({ error: 'Failed to update product' })
  }
})

// Helper to delete image file
function deleteImageFile(imagePath) {
  if (imagePath && imagePath.startsWith('/uploads/')) {
    const filePath = path.join(__dirname, '..', imagePath)
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath)
    }
  }
}

// DELETE - Remove product
app.delete('/api/products/:id', (req, res) => {
  try {
    const db = readProductsDB()
    const index = db.products.findIndex(p => p.id === parseInt(req.params.id))
    if (index === -1) {
      return res.status(404).json({ error: 'Product not found' })
    }

    const deleted = db.products.splice(index, 1)[0]

    // Delete main image
    deleteImageFile(deleted.image)

    // Delete album images
    if (deleted.images && Array.isArray(deleted.images)) {
      deleted.images.forEach(img => deleteImageFile(img))
    }

    writeProductsDB(db)
    res.json({ message: 'Product deleted', product: deleted })
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete product' })
  }
})

// ============ Cart API (Per Browser) ============

// Helper to get browser cart
function getBrowserCart(db, browserId) {
  if (!db.carts[browserId]) {
    db.carts[browserId] = []
  }
  return db.carts[browserId]
}

// GET cart items for specific browser
app.get('/api/cart/:browserId', (req, res) => {
  try {
    const db = readCartDB()
    const cart = getBrowserCart(db, req.params.browserId)
    res.json(cart)
  } catch (error) {
    res.status(500).json({ error: 'Failed to read cart' })
  }
})

// POST - Add item to cart
app.post('/api/cart/:browserId', (req, res) => {
  try {
    const db = readCartDB()
    const browserId = req.params.browserId
    const cart = getBrowserCart(db, browserId)
    const { productId, name, price, image, color, size, height, quantity = 1 } = req.body

    // Check if item already in cart (same product + options)
    const existingIndex = cart.findIndex(item =>
      item.productId === productId &&
      item.color === color &&
      item.size === size &&
      item.height === height
    )

    if (existingIndex !== -1) {
      cart[existingIndex].quantity += quantity
    } else {
      cart.push({
        id: cart.length > 0 ? Math.max(...cart.map(i => i.id)) + 1 : 1,
        productId,
        name,
        price,
        image,
        color,
        size,
        height,
        quantity,
        addedAt: new Date().toISOString()
      })
    }

    db.carts[browserId] = cart
    writeCartDB(db)
    res.status(201).json(cart)
  } catch (error) {
    res.status(500).json({ error: 'Failed to add to cart' })
  }
})

// PUT - Update cart item quantity
app.put('/api/cart/:browserId/:id', (req, res) => {
  try {
    const db = readCartDB()
    const cart = getBrowserCart(db, req.params.browserId)
    const index = cart.findIndex(item => item.id === parseInt(req.params.id))

    if (index === -1) {
      return res.status(404).json({ error: 'Cart item not found' })
    }

    cart[index] = {
      ...cart[index],
      ...req.body,
      id: parseInt(req.params.id)
    }
    db.carts[req.params.browserId] = cart
    writeCartDB(db)
    res.json(cart[index])
  } catch (error) {
    res.status(500).json({ error: 'Failed to update cart item' })
  }
})

// DELETE - Remove item from cart
app.delete('/api/cart/:browserId/:id', (req, res) => {
  try {
    const db = readCartDB()
    const cart = getBrowserCart(db, req.params.browserId)
    const index = cart.findIndex(item => item.id === parseInt(req.params.id))

    if (index === -1) {
      return res.status(404).json({ error: 'Cart item not found' })
    }

    const deleted = cart.splice(index, 1)
    db.carts[req.params.browserId] = cart
    writeCartDB(db)
    res.json({ message: 'Item removed from cart', item: deleted[0] })
  } catch (error) {
    res.status(500).json({ error: 'Failed to remove from cart' })
  }
})

// DELETE - Clear entire cart for browser
app.delete('/api/cart/:browserId', (req, res) => {
  try {
    const db = readCartDB()
    db.carts[req.params.browserId] = []
    writeCartDB(db)
    res.json({ message: 'Cart cleared' })
  } catch (error) {
    res.status(500).json({ error: 'Failed to clear cart' })
  }
})

// ============ Orders API ============

// POST - Create new order and send email
app.post('/api/orders', async (req, res) => {
  try {
    const db = readOrdersDB()
    const orderData = req.body

    const newOrder = {
      id: db.orders.length > 0 ? Math.max(...db.orders.map(o => o.id)) + 1 : 1,
      orderNumber: `ORD-${Date.now()}`,
      ...orderData,
      status: 'pending',
      createdAt: new Date().toISOString()
    }

    db.orders.push(newOrder)
    writeOrdersDB(db)

    // Generate order HTML for email
    const itemsHtml = orderData.items.map(item => `
      <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">
          <img src="${item.image}" alt="${item.name}" style="width: 60px; height: 80px; object-fit: cover;">
        </td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">
          <strong>${item.name}</strong><br>
          <small>${item.color || ''} / ${item.size || ''} / ${item.height || ''}</small><br>
          <small>Qty: ${item.quantity}</small>
        </td>
        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">
          E£${(item.price * item.quantity).toFixed(2)}
        </td>
      </tr>
    `).join('')

    const emailHtml = `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: #c9a66b; padding: 20px; text-align: center;">
          <h1 style="color: white; margin: 0;">HOOR</h1>
        </div>

        <div style="padding: 20px;">
          <h2 style="color: #333;">New Order #${newOrder.orderNumber}</h2>

          <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Customer Information</h3>
            <p><strong>Contact:</strong> ${orderData.contact}</p>
            <p><strong>Name:</strong> ${orderData.delivery.firstName} ${orderData.delivery.lastName}</p>
            <p><strong>Phone:</strong> ${orderData.delivery.phone}</p>
            <p><strong>Address:</strong> ${orderData.delivery.address}, ${orderData.delivery.city}, ${orderData.delivery.governorate}</p>
          </div>

          <h3>Order Items</h3>
          <table style="width: 100%; border-collapse: collapse;">
            ${itemsHtml}
          </table>

          <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #333;">
            <p style="display: flex; justify-content: space-between;"><span>Subtotal:</span> <span>E£${orderData.subtotal.toFixed(2)}</span></p>
            <p style="display: flex; justify-content: space-between;"><span>Shipping:</span> <span>E£${orderData.shipping.toFixed(2)}</span></p>
            <p style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;"><span>Total:</span> <span>E£${orderData.total.toFixed(2)}</span></p>
          </div>

          <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px;">
            <p style="margin: 0;"><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
          </div>
        </div>

        <div style="background: #333; color: white; padding: 15px; text-align: center;">
          <p style="margin: 0;">Thank you for your order!</p>
        </div>
      </div>
    `

    // Send email to store owner
    const storeEmail = process.env.STORE_EMAIL || 'ahmedelsayedweb@gmail.com'

    try {
      await transporter.sendMail({
        from: '"HOOR Store" <noreply@hoor.com>',
        to: storeEmail,
        subject: `New Order #${newOrder.orderNumber}`,
        html: emailHtml
      })
      console.log('Order email sent successfully')
    } catch (emailError) {
      console.error('Failed to send email:', emailError.message)
      // Don't fail the order if email fails
    }

    res.status(201).json({
      success: true,
      order: newOrder,
      message: 'Order placed successfully'
    })
  } catch (error) {
    console.error('Order error:', error)
    res.status(500).json({ error: 'Failed to create order' })
  }
})

// GET - Get all orders
app.get('/api/orders', (req, res) => {
  try {
    const db = readOrdersDB()
    res.json(db.orders)
  } catch (error) {
    res.status(500).json({ error: 'Failed to read orders' })
  }
})

// SPA fallback - serve index.html for all non-API routes
if (fs.existsSync(distPath)) {
  app.get('*', (req, res) => {
    res.sendFile(path.join(distPath, 'index.html'))
  })
}

// Start server
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`)
  console.log(`API endpoints:`)
  console.log(`  Products:`)
  console.log(`    GET    /api/products     - Get all products`)
  console.log(`    GET    /api/products/:id - Get single product`)
  console.log(`    POST   /api/products     - Add new product`)
  console.log(`    PUT    /api/products/:id - Update product`)
  console.log(`    DELETE /api/products/:id - Delete product`)
  console.log(`  Cart:`)
  console.log(`    GET    /api/cart         - Get cart items`)
  console.log(`    POST   /api/cart         - Add item to cart`)
  console.log(`    PUT    /api/cart/:id     - Update cart item`)
  console.log(`    DELETE /api/cart/:id     - Remove item from cart`)
  console.log(`    DELETE /api/cart         - Clear entire cart`)
})
