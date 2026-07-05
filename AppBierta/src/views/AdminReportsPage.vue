<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Reportes de Ventas</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-content class="ion-padding" style="--background: #f7f9fc;">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>
      <!-- Filtros -->
      <ion-card class="pro-card">
        <ion-card-header>
          <ion-card-title>Filtros de Búsqueda</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-grid>
            <ion-row>
              <ion-col size="12" size-md="4">
                <ion-item class="pro-input">
                  <ion-label position="stacked">Fecha Inicio</ion-label>
                  <ion-input type="date" v-model="filters.start_date"></ion-input>
                </ion-item>
              </ion-col>
              <ion-col size="12" size-md="4">
                <ion-item class="pro-input">
                  <ion-label position="stacked">Fecha Fin</ion-label>
                  <ion-input type="date" v-model="filters.end_date"></ion-input>
                </ion-item>
              </ion-col>
              <ion-col size="12" size-md="4" class="ion-align-self-end" style="display: flex; gap: 10px;">
                <ion-button expand="block" color="primary" @click="fetchReports" :disabled="loading" style="flex: 1;">
                  <ion-spinner v-if="loading" name="crescent"></ion-spinner>
                  <span v-else>Filtrar</span>
                </ion-button>
                <ion-button expand="block" color="success" @click="exportToExcel" :disabled="loading || !hasData" style="flex: 1;">
                  <span v-if="!loading">Excel</span>
                </ion-button>
              </ion-col>
            </ion-row>
          </ion-grid>
        </ion-card-content>
      </ion-card>

      <!-- Selector de Reporte -->
      <div class="report-selector ion-margin-vertical">
        <ion-segment v-model="activeReport" color="primary">
          <ion-segment-button value="sales">
            <ion-label>Ventas</ion-label>
          </ion-segment-button>
          <ion-segment-button value="products">
            <ion-label>Productos</ion-label>
          </ion-segment-button>
          <ion-segment-button value="users">
            <ion-label>Clientes</ion-label>
          </ion-segment-button>
        </ion-segment>
      </div>

      <!-- TABLA DETALLADA DE VENTAS -->
      <ion-card v-if="activeReport === 'sales' && hasData" class="pro-card">
        <ion-card-header>
          <ion-card-title class="ion-text-center">Detalle de Ventas</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div class="table-responsive">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Pedido #</th>
                  <th>Fecha</th>
                  <th>Cliente</th>
                  <th>Productos</th>
                  <th>Costo (Bs)</th>
                  <th>Ingreso (Bs)</th>
                  <th>Margen (Bs)</th>
                  <th>Método Pago</th>
                  <th>Tipo</th>
                  <th>Atendido Por</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in groupedSales" :key="order.pedido_id">
                  <td>{{ order.pedido_id }}</td>
                  <td>{{ new Date(order.fecha).toLocaleString() }}</td>
                  <td>{{ order.cliente }}</td>
                  <td>
                    <ul style="margin: 0; padding-left: 15px;">
                      <li v-for="prod in order.productos" :key="prod">{{ prod }}</li>
                    </ul>
                  </td>
                  <td>{{ order.costo_total.toFixed(2) }}</td>
                  <td>{{ order.ingreso_total.toFixed(2) }}</td>
                  <td>{{ order.margen.toFixed(2) }}</td>
                  <td>
                    <ion-badge :color="order.metodo_pago === 'cash' ? 'success' : 'tertiary'">
                      {{ order.metodo_pago === 'cash' ? 'Efectivo' : 'QR' }}
                    </ion-badge>
                  </td>
                  <td>{{ order.tipo_pedido === 'delivery' ? 'Delivery' : 'Tienda' }}</td>
                  <td>{{ order.empleado_delivery || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </ion-card-content>
      </ion-card>

      <!-- Gráficos -->
      <ion-card class="pro-card chart-card">
        <ion-card-header>
          <ion-card-title class="ion-text-center">{{ reportTitle }}</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div v-if="loading" class="ion-text-center ion-padding">
            <ion-spinner color="primary"></ion-spinner>
            <p>Calculando datos...</p>
          </div>
          
          <div v-else-if="!hasData" class="ion-text-center ion-padding empty-state">
            <p>No hay datos disponibles para las fechas seleccionadas.</p>
          </div>

          <div v-if="activeReport === 'sales'">
            <div class="sales-summary ion-margin-bottom">
              <ion-row>
                <ion-col size="6">
                  <div class="summary-box">
                    <p>Total Ingreso</p>
                    <h3 style="color: #04644c;">
                      {{ formatCurrency(salesSummary.ingreso) }} Bs
                      <span v-if="salesSummary.delivery > 0" style="font-size: 0.9rem; color: var(--ion-color-medium); display: block; margin-top: 5px;">
                        (+ {{ formatCurrency(salesSummary.delivery) }} Delivery)
                      </span>
                    </h3>
                  </div>
                </ion-col>
                <ion-col size="6">
                  <div class="summary-box">
                    <p>Ganancia (Margen)</p>
                    <h3 style="color: #2dd36f;">{{ formatCurrency(salesSummary.margen) }} Bs</h3>
                  </div>
                </ion-col>
              </ion-row>
            </div>
            <div class="chart-container">
              <Line :data="salesChartData" :options="lineOptions" />
            </div>
          </div>
          <div v-else-if="activeReport === 'products'" class="chart-container">
            <Bar :data="productsChartData" :options="barOptions" />
          </div>
            
            <div v-if="activeReport === 'users'" class="clients-list" style="overflow-y: auto; max-height: 350px;">
              <ion-list lines="full">
                <ion-item v-for="client in rawData.clients_list" :key="client.id">
                  <ion-label>
                    <h2 style="font-weight: bold;">{{ client.apellidos }} {{ client.nombre }}</h2>
                    <p>{{ client.email }} | Tel: {{ client.telefono || 'N/A' }}</p>
                  </ion-label>
                  <ion-note slot="end" color="warning" style="font-weight: bold;">{{ client.loyalty_points }} pts</ion-note>
                </ion-item>
              </ion-list>
            </div>
        </ion-card-content>
      </ion-card>
      
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonCard, IonCardHeader, 
  IonCardTitle, IonCardContent, IonGrid, IonRow, IonCol, IonItem, IonLabel, 
  IonInput, IonButton, IonSpinner, IonSegment, IonSegmentButton, IonRefresher, IonRefresherContent, IonButtons, IonBackButton
} from '@ionic/vue';
import axios from 'axios';
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, 
  BarElement, Title, Tooltip, Legend, Filler
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import * as ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

// Registrar Chart.js
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

// Estado
const loading = ref(false);
const activeReport = ref('sales');
const rawData = ref<{sales: any[], products: any[], users: any[], clients_list: any[], detailed_sales?: any[]}>({
  sales: [],
  products: [],
  users: [],
  clients_list: [],
  detailed_sales: []
});

const today = new Date();
const thirtyDaysAgo = new Date();
thirtyDaysAgo.setDate(today.getDate() - 30);

const filters = ref({
  start_date: thirtyDaysAgo.toISOString().split('T')[0],
  end_date: today.toISOString().split('T')[0]
});

// Computed Data para Charts
const salesChartData = computed(() => {
  return {
    labels: rawData.value.sales.map(s => s.date),
    datasets: [
      {
        label: 'Ingresos Totales (Bs)',
        backgroundColor: 'rgba(56, 128, 255, 0.2)',
        borderColor: '#3880ff',
        borderWidth: 2,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#3880ff',
        tension: 0.4,
        fill: true,
        data: rawData.value.sales.map(s => parseFloat(s.total))
      }
    ]
  }
});

const productsChartData = computed(() => {
  return {
    labels: rawData.value.products.map(p => p.product),
    datasets: [
      {
        label: 'Unidades Vendidas',
        backgroundColor: '#2dd36f',
        borderRadius: 6,
        data: rawData.value.products.map(p => parseInt(p.total_quantity))
      }
    ]
  }
});

const usersChartData = computed(() => {
  return {
    labels: rawData.value.users.map(u => u.date),
    datasets: [
      {
        label: 'Nuevos Clientes Registrados',
        backgroundColor: 'rgba(255, 196, 9, 0.2)',
        borderColor: '#ffc409',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
        data: rawData.value.users.map(u => u.total)
      }
    ]
  }
});

const hasData = computed(() => {
  if (activeReport.value === 'sales') return rawData.value.sales && rawData.value.sales.length > 0;
  if (activeReport.value === 'products') return rawData.value.products && rawData.value.products.length > 0;
  if (activeReport.value === 'users') return rawData.value.clients_list && rawData.value.clients_list.length > 0;
  return false;
});

const reportTitle = computed(() => {
  if (activeReport.value === 'sales') return 'Evolución de Ventas y Ganancias';
  if (activeReport.value === 'products') return 'Top 10 Productos Más Vendidos';
  if (activeReport.value === 'users') return 'Lista de Clientes Registrados';
  return '';
});

const formatCurrency = (value: number) => {
  return value.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const salesSummary = computed(() => {
  let ingreso = 0;
  let margen = 0;
  let delivery = 0;
  if (rawData.value.detailed_sales) {
    const ordersCounted = new Set();
    rawData.value.detailed_sales.forEach((s: any) => {
      const pCompra = parseFloat(s.precio_compra || '0');
      const pVenta = parseFloat(s.precio_venta || '0');
      const cant_vendida = parseInt(s.cantidad_vendida || '0');
      const cant_lotes = parseInt(s.cantidad_lotes || '0');
      const deliveryCost = parseFloat(s.delivery_cost || '0');
      
      ingreso += (pVenta * cant_vendida);
      if (pVenta > 0) {
        margen += ((pVenta * cant_vendida) - (pCompra * cant_lotes));
      }
      
      if (deliveryCost > 0 && !ordersCounted.has(s.pedido_id)) {
        delivery += deliveryCost;
      }
      ordersCounted.add(s.pedido_id);
    });
  }
  return { ingreso, margen, delivery };
});

const groupedSales = computed(() => {
  if (!rawData.value.detailed_sales) return [];
  const map = new Map();
  rawData.value.detailed_sales.forEach((s: any) => {
    if (!map.has(s.pedido_id)) {
      map.set(s.pedido_id, {
        pedido_id: s.pedido_id,
        fecha: s.fecha,
        cliente: s.cliente,
        metodo_pago: s.metodo_pago,
        tipo_pedido: s.tipo_pedido,
        empleado_delivery: s.empleado_delivery,
        delivery_cost: parseFloat(s.delivery_cost || 0),
        costo_total: 0,
        ingreso_total: 0,
        margen: 0,
        productos: []
      });
    }
    const order = map.get(s.pedido_id);
    const pCompra = parseFloat(s.precio_compra || 0);
    const pVenta = parseFloat(s.precio_venta || 0);
    const cant_vendida = parseInt(s.cantidad_vendida || 0);
    const cant_lotes = parseInt(s.cantidad_lotes || 0);
    
    order.costo_total += pCompra * cant_lotes;
    order.ingreso_total += pVenta * cant_vendida;
    
    // Si la venta no es un canje por puntos, sumar margen
    if (pVenta > 0) {
        order.margen += (pVenta * cant_vendida) - (pCompra * cant_lotes);
    }
    
    const prodName = pVenta === 0 ? `${s.producto} (Canje)` : `${s.producto} (Bs. ${pVenta})`;
    order.productos.push(`${cant_vendida}x ${prodName}`);
  });
  
  // Agregar costo de delivery al total de la orden si corresponde
  const result = Array.from(map.values());
  result.forEach((order: any) => {
    if (order.delivery_cost > 0) {
      order.ingreso_total += order.delivery_cost;
      order.productos.push(`1x Delivery (+ Bs. ${order.delivery_cost})`);
    }
  });
  
  return result;
});

// Opciones de Gráficos (Modernas)
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  color: '#888',
  plugins: {
    legend: { position: 'bottom' as const, labels: { color: '#888' } }
  },
  scales: {
    y: { 
      beginAtZero: true, 
      grid: { color: 'rgba(136, 136, 136, 0.2)', borderDash: [5, 5] },
      ticks: { color: '#888' }
    },
    x: { 
      grid: { display: false },
      ticks: { color: '#888' }
    }
  }
};

const lineOptions = chartOptions;
const barOptions = chartOptions;

// Fetch API
const fetchReports = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/reports', { params: filters.value });
    rawData.value = res.data;
  } catch (error) {
    console.error("Error fetching reports", error);
  } finally {
    loading.value = false;
  }
};

const handleRefresh = async (event: any) => {
  await fetchReports();
  event.target.complete();
};

// Export to Excel
const exportToExcel = async () => {
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet("Reporte");

  let filename = '';

  if (activeReport.value === 'sales') {
    worksheet.columns = [
      { header: 'Pedido ID', key: 'id', width: 10 },
      { header: 'Fecha', key: 'fecha', width: 20 },
      { header: 'Cliente', key: 'cliente', width: 25 },
      { header: 'Producto', key: 'producto', width: 30 },
      { header: 'Cantidad', key: 'cantidad', width: 10 },
      { header: 'Precio Compra (Bs)', key: 'pCompra', width: 18 },
      { header: 'Precio Venta (Bs)', key: 'pVenta', width: 18 },
      { header: 'Costo Total (Bs)', key: 'costo', width: 18 },
      { header: 'Ingreso Total (Bs)', key: 'ingreso', width: 18 },
      { header: 'Margen (Bs)', key: 'margen', width: 15 },
      { header: 'Método Pago', key: 'pago', width: 18 },
      { header: 'Tipo', key: 'tipo', width: 12 },
      { header: 'Atendido Por', key: 'atendido', width: 25 }
    ];

    let sumCosto = 0;
    let sumIngreso = 0;
    let sumMargen = 0;

    if (rawData.value.detailed_sales) {
      rawData.value.detailed_sales.forEach((s: any) => {
        const pCompra = parseFloat(s.precio_compra || '0');
        const pVenta = parseFloat(s.precio_venta || '0');
        const cant_vendida = parseInt(s.cantidad_vendida || '0');
        const cant_lotes = parseInt(s.cantidad_lotes || '0');
        const isCanje = (pVenta === 0);
        
        const costoTotal = pCompra * cant_lotes;
        const ingresoTotal = pVenta * cant_vendida;
        const margen = isCanje ? 0 : (ingresoTotal - costoTotal);

        if (!isCanje) {
          sumCosto += costoTotal;
          sumIngreso += ingresoTotal;
          sumMargen += margen;
        }

        let metodoPago = s.metodo_pago === 'cash' ? 'Efectivo' : (s.metodo_pago === 'qr' ? 'QR' : s.metodo_pago);
        if (isCanje) {
          metodoPago = 'Puntos (Canje)';
        }

        const row = worksheet.addRow({
          id: s.pedido_id,
          fecha: s.fecha,
          cliente: s.cliente,
          producto: s.producto,
          cantidad: cant_vendida,
          pCompra: pCompra,
          pVenta: pVenta,
          costo: costoTotal,
          ingreso: ingresoTotal,
          margen: margen,
          pago: metodoPago,
          tipo: s.tipo_pedido === 'delivery' ? 'Delivery' : 'Recojo en Tienda',
          atendido: s.empleado_delivery || 'N/A'
        });

        if (isCanje) {
          row.eachCell((cell) => {
            cell.fill = {
              type: 'pattern',
              pattern: 'solid',
              fgColor: { argb: 'FFE6F0FF' } // Light blue for canjes
            };
          });
        }
      });
      
      // Filas de Totales
      worksheet.addRow({});
      const totalRow = worksheet.addRow({
        pVenta: 'TOTALES:',
        costo: sumCosto,
        ingreso: sumIngreso,
        margen: sumMargen
      });
      totalRow.font = { bold: true };
    }
    filename = 'reporte_ventas_detallado.xlsx';
  } else if (activeReport.value === 'products') {
    worksheet.columns = [
      { header: 'Producto', key: 'producto', width: 40 },
      { header: 'Unidades Vendidas', key: 'unidades', width: 25 },
      { header: 'Precio Promedio de Venta (Bs)', key: 'precio', width: 30 },
      { header: 'Categoría', key: 'categoria', width: 25 }
    ];
    // Adding more details if possible, though rawData.products from the endpoint currently has product and total_quantity.
    // If it lacks details, we just show what we have neatly.
    rawData.value.products.forEach((p: any) => {
      worksheet.addRow({ 
        producto: p.product, 
        unidades: parseInt(p.total_quantity),
        precio: p.precio_venta ? parseFloat(p.precio_venta) : 'Variado',
        categoria: p.categoria || 'N/A'
      });
    });
    filename = 'reporte_productos.xlsx';
  } else if (activeReport.value === 'users') {
    worksheet.columns = [
      { header: 'Apellidos', key: 'apellidos', width: 25 },
      { header: 'Nombres', key: 'nombres', width: 25 },
      { header: 'Email', key: 'email', width: 35 },
      { header: 'Teléfono', key: 'telefono', width: 15 },
      { header: 'Puntos Acumulados', key: 'puntos', width: 20 },
      { header: 'Fecha de Registro', key: 'fecha', width: 20 }
    ];
    rawData.value.clients_list.forEach((c: any) => {
      worksheet.addRow({
        apellidos: c.apellidos,
        nombres: c.nombre,
        email: c.email,
        telefono: c.telefono || 'Sin registro',
        puntos: c.loyalty_points,
        fecha: new Date(c.created_at).toLocaleDateString()
      });
    });
    filename = 'reporte_clientes.xlsx';
  }

  if (worksheet.rowCount === 0) return;

  // Style Header
  const headerRow = worksheet.getRow(1);
  headerRow.eachCell((cell) => {
    cell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF10DC60' } // Green matching ionic success color
    };
    cell.font = {
      color: { argb: 'FFFFFFFF' },
      bold: true
    };
    cell.border = {
      top: { style: 'thin' },
      left: { style: 'thin' },
      bottom: { style: 'thin' },
      right: { style: 'thin' }
    };
  });

  // Borders for all cells
  worksheet.eachRow((row, rowNumber) => {
    if (rowNumber > 1 && row.hasValues) {
      row.eachCell((cell) => {
        if (!cell.border) {
          cell.border = {
            top: { style: 'thin', color: { argb: 'FFCCCCCC' } },
            left: { style: 'thin', color: { argb: 'FFCCCCCC' } },
            bottom: { style: 'thin', color: { argb: 'FFCCCCCC' } },
            right: { style: 'thin', color: { argb: 'FFCCCCCC' } }
          };
        }
      });
    }
  });

  const buffer = await workbook.xlsx.writeBuffer();
  saveAs(new Blob([buffer]), filename);
};

onMounted(() => {
  fetchReports();
});
</script>

<style scoped>
.pro-card {
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
  background: #ffffff;
}
.pro-input {
  border-radius: 8px;
  margin-bottom: 10px;
}
.chart-card {
  min-height: 400px;
}
.chart-container {
  position: relative;
  height: 350px;
  width: 100%;
}
.empty-state {
  color: #6c757d;
  font-size: 1.1em;
  margin-top: 50px;
}
.report-selector {
  max-width: 600px;
  margin: 0 auto 20px auto;
}
.sales-summary {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 10px;
  border: 1px solid #e9ecef;
}
.summary-box {
  text-align: center;
}
.summary-box p {
  margin: 0 0 5px 0;
  color: #6c757d;
  font-size: 0.9em;
  font-weight: 600;
}
.summary-box h3 {
  margin: 0;
  font-weight: 800;
  font-size: 1.5em;
}

/* Table Styles for Dark Mode Support */
.table-responsive {
  overflow-x: auto;
  margin-top: 10px;
}
.styled-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85em;
  font-family: sans-serif;
  min-width: 1000px;
  background-color: #ffffff;
  color: #333333;
  border-radius: 8px;
  overflow: hidden;
}
.styled-table thead tr {
  background-color: #04644c;
  color: #ffffff;
  text-align: left;
}
.styled-table th,
.styled-table td {
  padding: 10px 12px;
  border: 1px solid #e0e0e0;
}
.styled-table tbody tr {
  border-bottom: 1px solid #e0e0e0;
}
.styled-table tbody tr:nth-of-type(even) {
  background-color: #f9f9f9;
}
.styled-table tbody tr:last-of-type {
  border-bottom: 2px solid #04644c;
}
.sales-summary {
  background: #ffffff;
  border-radius: 8px;
  padding: 10px;
  border: 1px solid #e0e0e0;
}
.summary-box p {
  margin: 0 0 5px 0;
  color: #aaaaaa;
  font-size: 0.9em;
  font-weight: 600;
}
@media (prefers-color-scheme: dark) {
  .pro-card, .sales-summary, .styled-table {
    background-color: #1e2023 !important;
    color: #ffffff !important;
    border-color: #333333 !important;
  }
  .styled-table tbody tr:nth-of-type(even) {
    background-color: #2a2c2f !important;
  }
  .styled-table th, .styled-table td, .styled-table tbody tr {
    border-color: #333333 !important;
  }
  ion-segment-button {
    --color: #cccccc;
    --color-checked: #ffffff;
    --indicator-color: #04644c;
  }
}
</style>
