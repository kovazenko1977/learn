import axios from 'axios';

export const PrintService = {
  async generatePDF(templateId: number, productId: number, quantity: number = 1) {
    try {
      const response = await axios.post('/api/print', {
        template_id: templateId,
        product_id: productId,
        quantity: quantity
      }, { responseType: 'blob' });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `label_${templateId}_${Date.now()}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Failed to generate PDF:', error);
      throw error;
    }
  }
};
