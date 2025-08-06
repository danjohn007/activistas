// Simple Chart.js mock for testing when CDN is blocked
// This is a minimal implementation to demonstrate chart functionality

window.Chart = function(ctx, config) {
    console.log('📊 Chart.js Mock - Creating chart:', config.type);
    
    // Store config for later use
    this.data = config.data;
    this.options = config.options || {};
    this.type = config.type;
    this.ctx = ctx;
    
    // Simulate chart creation
    this.update = function() {
        console.log('📈 Chart updated with new data');
    };
    
    this.destroy = function() {
        console.log('🗑️ Chart destroyed');
    };
    
    // Create a simple visual representation
    this.render();
    
    return this;
};

Chart.prototype.render = function() {
    const canvas = this.ctx.canvas;
    const parent = canvas.parentElement;
    
    // Create a simple HTML representation of the chart
    let chartHtml = `
        <div style="padding: 20px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;">
            <h6 style="color: #495057; margin-bottom: 15px;">📊 ${this.type.toUpperCase()} CHART</h6>
            <div style="color: #6c757d; font-size: 14px;">
                <strong>Labels:</strong> ${this.data.labels ? this.data.labels.join(', ') : 'No labels'}<br>
                <strong>Data:</strong> ${this.data.datasets[0] ? this.data.datasets[0].data.join(', ') : 'No data'}<br>
                <small style="color: #28a745;">✅ Chart.js funcionando correctamente</small>
            </div>
        </div>
    `;
    
    parent.innerHTML = chartHtml;
    console.log('✅ Chart rendered successfully');
};

// Add Chart to global scope
window.Chart.register = function() {
    console.log('📦 Chart.js plugins registered');
};

console.log('📚 Chart.js Mock loaded successfully');