let test = 1 

function onDecay() {
    let test = 0;
}

if (test == 1) {
    currentTarget = { whole: 0, num: 1, den: 2, slicesPerPizza: 8, pizzaCount: 1 };
    renderPizzas();
};

function renderPizzas() {
    const container = document.getElementById('pizzasArea');
    container.innerHTML = '';

    for (let p = 0; p < currentTarget.pizzaCount; p++) {
        const wrapper = document.createElement('div');
        wrapper.className = 'pizza-wrapper';

        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("viewBox", "0 0 260 260");
        svg.classList.add("pizza");

        // Prato/Base por baixo da pizza (fica visível quando o pedaço é comido)
        const plate = document.createElementNS("http://www.w3.org/2000/svg", "circle");
        plate.setAttribute("cx", "130");
        plate.setAttribute("cy", "130");
        plate.setAttribute("r", "122");
        plate.setAttribute("fill", "#e0d6c3");
        plate.setAttribute("stroke", "#c7b89d");
        plate.setAttribute("stroke-width", "3");
        svg.appendChild(plate);

        const totalSlices = currentTarget.slicesPerPizza;
        const angleStep = 360 / totalSlices;

        for (let i = 0; i < totalSlices; i++) {
            const startAngle = i * angleStep;
            const endAngle = (i + 1) * angleStep;

            // Grupo do Pedaço (Contém Borda + Queijo + Peperonis para sumirem juntos)
            const sliceGroup = document.createElementNS("http://www.w3.org/2000/svg", "g");
            sliceGroup.classList.add("slice");
            sliceGroup.dataset.pizzaIndex = p;
            sliceGroup.dataset.sliceIndex = i;

            // 1. Borda/Massa Dourada do Pedaço (Raio 120)
            const crustSlice = document.createElementNS("http://www.w3.org/2000/svg", "path");
            crustSlice.setAttribute("d", getSlicePath(130, 130, 120, startAngle, endAngle));
            crustSlice.setAttribute("fill", "#d35400");
            crustSlice.setAttribute("stroke", "#a04000");
            crustSlice.setAttribute("stroke-width", "1.5");
            sliceGroup.appendChild(crustSlice);

            // 2. Queijo Amarelo do Pedaço (Raio 110)
            const cheeseSlice = document.createElementNS("http://www.w3.org/2000/svg", "path");
            cheeseSlice.setAttribute("d", getSlicePath(130, 130, 110, startAngle, endAngle));
            cheeseSlice.setAttribute("fill", "#f1c40f");
            cheeseSlice.setAttribute("stroke", "#f39c12");
            cheeseSlice.setAttribute("stroke-width", "1.5");
            sliceGroup.appendChild(cheeseSlice);

            // 3. Peperonis do Pedaço
            addPepperonisToSlice(sliceGroup, startAngle, endAngle, totalSlices);

            sliceGroup.onclick = () => eatSlice(sliceGroup);
            svg.appendChild(sliceGroup);
        }

        wrapper.appendChild(svg);
        container.appendChild(wrapper);
    }
}

function addPepperonisToSlice(group, startAngle, endAngle, totalSlices) {
    const midAngle = (startAngle + endAngle) / 2;
    const radiusDistances = totalSlices > 8 ? [70] : [55, 88];

    radiusDistances.forEach((rDist, idx) => {
        const angleOffset = (idx % 2 === 0 ? -3 : 3);
        const rad = ((midAngle + angleOffset) - 90) * Math.PI / 180;
        const px = 130 + rDist * Math.cos(rad);
        const py = 130 + rDist * Math.sin(rad);

        const pep = document.createElementNS("http://www.w3.org/2000/svg", "circle");
        pep.setAttribute("cx", px);
        pep.setAttribute("cy", py);
        pep.setAttribute("r", totalSlices > 8 ? "8" : "11");
        pep.setAttribute("fill", "#c0392b");
        pep.setAttribute("stroke", "#922b21");
        pep.setAttribute("stroke-width", "1.5");
        group.appendChild(pep);
    });
}

function getSlicePath(cx, cy, r, startAngle, endAngle) {
    const startRad = (startAngle - 90) * Math.PI / 180;
    const endRad = (endAngle - 90) * Math.PI / 180;

    const x1 = cx + r * Math.cos(startRad);
    const y1 = cy + r * Math.sin(startRad);
    const x2 = cx + r * Math.cos(endRad);
    const y2 = cy + r * Math.sin(endRad);

    const largeArc = (endAngle - startAngle) > 180 ? 1 : 0;
    return `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`;
}
