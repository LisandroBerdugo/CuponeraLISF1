// js/cart.js
const CART_KEY = 'lc_cart';

const Cart = {
  _load() {
    try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
    catch { return []; }
  },
  _save(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    Cart.updateIcon();
  },
  all() { return Cart._load(); },
  add(item) {
    const cart = Cart._load();
    const idx = cart.findIndex(p => p.id === item.id);
    if (idx >= 0) { cart[idx].qty += item.qty || 1; }
    else { cart.push({ id: item.id, title: item.title, price: item.price, qty: item.qty || 1 }); }
    Cart._save(cart);
  },
  remove(id) {
    const cart = Cart._load().filter(p => p.id !== id);
    Cart._save(cart);
  },
  setQty(id, qty) {
    const cart = Cart._load().map(p => (p.id === id ? { ...p, qty: Math.max(1, qty|0) } : p));
    Cart._save(cart);
  },
  clear() { Cart._save([]); },
  totals() {
    const cart = Cart._load();
    const items = cart.reduce((a,b)=>a+b.qty,0);
    const amount = cart.reduce((a,b)=>a+(b.price*b.qty),0);
    return { items, amount };
  },
  updateIcon() {
    const el = document.getElementById('cart-plus-sign');
    if (!el) return;
    const { items } = Cart.totals();
    el.textContent = items > 0 ? ` ${items}` : '';
  }
};

document.addEventListener('DOMContentLoaded', Cart.updateIcon);
