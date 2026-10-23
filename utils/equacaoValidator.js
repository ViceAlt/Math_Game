// utils/equacaoValidator.js
module.exports = {
  validateEquation: function(eq) {
    const x = eq.solucao;
    // Calculate sides
    const ladoEsq = (eq.esq.x * x) + eq.esq.blocos - eq.esq.baloes;
    const ladoDir = (eq.dir.x * x) + eq.dir.blocos - eq.dir.baloes;
    const equilibrio = Math.abs(ladoEsq - ladoDir) < 0.001;
    const messages = [];
    if (!equilibrio) {
      messages.push({type: 'error', text: `Desbalanceado: Lado esq ${ladoEsq}, Lado dir ${ladoDir}`});
    }
    if (x <= 0 || !Number.isInteger(x)) {
      messages.push({type: 'warning', text: `Solução x = ${x} não é inteiro positivo`});
    }
    if (eq.esq.x === 0 && eq.dir.x === 0) {
      messages.push({type: 'error', text: 'Nenhum x na equação'});
    }
    if (eq.nivel === 'experiente') {
      const xDiff = eq.esq.x - eq.dir.x;
      if (xDiff === 0) {
        messages.push({type: 'warning', text: 'Mesmo número de x dos dois lados'});
      }
    }
    return {equilibrio, messages};
  }
};
