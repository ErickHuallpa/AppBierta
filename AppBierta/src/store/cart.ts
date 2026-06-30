import { reactive } from 'vue';

export const cartState = reactive({
  items: [] as Array<{ product_id: number; name: string; price: number; quantity: number, image_url?: string, is_reward?: boolean, points_cost?: number, points_reward?: number }>,
  checkout: {
    order_type: 'delivery' as 'delivery' | 'pickup',
    notes: '',
    payment_method: 'cash' as 'cash' | 'qr' | 'points',
    location_id: null as number | null,
    nit: '',
    razon_social: ''
  },
  
  addItem(product: any, isReward: boolean = false) {
    this.addItems(product, 1, isReward);
  },

  addItems(product: any, quantity: number, isReward: boolean = false) {
    const existing = this.items.find(i => i.product_id === product.id && i.is_reward === isReward);
    if (existing) {
      existing.quantity += quantity;
    } else {
      this.items.push({
        product_id: product.id,
        name: product.name,
        price: isReward ? 0 : Number(product.precio_venta),
        quantity: quantity,
        image_url: product.image_url || `https://placehold.co/150x150/eeeeee/333333?text=${product.name}`,
        is_reward: isReward,
        points_cost: isReward ? Number(product.points_cost || 0) : 0,
        points_reward: isReward ? 0 : Number(product.points_reward || 0)
      });
    }
  },

  removeItem(productId: number, isReward: boolean = false) {
    const index = this.items.findIndex(i => i.product_id === productId && i.is_reward === isReward);
    if (index !== -1) {
      if (this.items[index].quantity > 1) {
        this.items[index].quantity--;
      } else {
        this.items.splice(index, 1);
      }
    }
  },

  removeProduct(productId: number, isReward: boolean = false) {
    const index = this.items.findIndex(i => i.product_id === productId && i.is_reward === isReward);
    if (index !== -1) {
      this.items.splice(index, 1);
    }
  },

  clearCart() {
    this.items = [];
    this.checkout.notes = '';
    this.checkout.nit = '';
    this.checkout.razon_social = '';
  },

  get totalItems() {
    return this.items.reduce((sum: number, item: any) => sum + item.quantity, 0);
  },
  
  get totalPrice() {
    return this.items.reduce((sum: number, item: any) => sum + (item.price * item.quantity), 0);
  },
  get totalPointsReward() {
    return this.items.reduce((sum: number, item: any) => sum + ((item.points_reward || 0) * item.quantity), 0);
  },
  get totalPointsCost() {
    return this.items.reduce((sum: number, item: any) => sum + ((item.points_cost || 0) * item.quantity), 0);
  },
});
