/**
 * Verification script for equacoes.json
 * Tests that each equation's solution is mathematically correct
 * and that the equation is solvable within the game's mechanics.
 */
// verifier_equacoes.js - Updated to use shared validator
const fs = require('fs');
const path = require('path');
const validator = require('../utils/equacaoValidator');

const equacoes = JSON.parse(fs.readFileSync(path.join(__dirname, 'equacoes.json'), 'utf8'));

let erros = 0;
let avisos = 0;

equacoes.forEach((eq) => {
    const {equilibrio, messages} = validator.validateEquation(eq);
    if (!equilibrio) {
        console.error(`❌ ERRO [${eq.id}]: Desbalanceado`);
        erros++;
    }
    // Log messages
    messages.forEach(msg => {
        if (msg.type === 'error') {
            console.error(`❌ ${msg.text} [${eq.id}]`);
            erros++;
        } else if (msg.type === 'warning') {
            console.warn(`⚠️ ${msg.text} [${eq.id}]`);
            avisos++;
        }
    });
    if (equilibrio) {
        // Format equation string
        let partes_esq = [];
        if (eq.esq.x > 0) partes_esq.push(eq.esq.x === 1 ? 'x' : `${eq.esq.x}x`);
        if (eq.esq.blocos > 0) partes_esq.push(`${eq.esq.blocos}`);
        if (eq.esq.baloes > 0) partes_esq.push(`(-${eq.esq.baloes})`);
        let partes_dir = [];
        if (eq.dir.x > 0) partes_dir.push(eq.dir.x === 1 ? 'x' : `${eq.dir.x}x`);
        if (eq.dir.blocos > 0) partes_dir.push(`${eq.dir.blocos}`);
        if (eq.dir.baloes > 0) partes_dir.push(`(-${eq.dir.baloes})`);
        const eqStr = `${partes_esq.join(' + ') || '0'} = ${partes_dir.join(' + ') || '0'}`;
        console.log(`✅ [${eq.id}] ${eq.nivel.padEnd(10)} ${eqStr.padEnd(35)} x = ${eq.solucao}`);
    }
});

console.log(`\n--- RESULTADO ---`);
console.log(`Total: ${equacoes.length} equações`);
console.log(`  Fácil:      ${equacoes.filter(e => e.nivel === 'facil').length}`);
console.log(`  Médio:      ${equacoes.filter(e => e.nivel === 'medio').length}`);
console.log(`  Experiente: ${equacoes.filter(e => e.nivel === 'experiente').length}`);
console.log(`Erros: ${erros}, Avisos: ${avisos}`);

if (erros > 0) process.exit(1);

