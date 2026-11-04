async function refreshMe() {
    try {
        const response = await fetch("main.php?ajax=1&t=" + Date.now());
    
        if (!response.ok) throw new Error("Erreur réseau");
        
        const data = await response.json();

        let htmlSig = "";
        data.signals.forEach(s => {
            let typeLower = s.type.toLowerCase();
            let emoji = (s.type === "OFF") ? "⚫" : ((s.type === "STOP") ? "🔴" : "🟡");
            
            htmlSig += `
                <tr>
                    <td><span class="badge status-${typeLower}">${emoji} ${s.type}</span></td>
                    <td><strong>${s.presse}</strong></td>
                    <td style="color: #666; font-size: 0.9em;">${s.date_debut}</td>
                </tr>`;
        });
        document.getElementById("tableSignals").innerHTML = htmlSig;

        let htmlEtat = "";
        let countRun = 0;
        
        data.etat.forEach(e => {
            let etatLower = e.etat.toLowerCase();
            let emo = "";
            let dot = "";

            if (e.etat === "run") {
                emo = "🟢";
                dot = '<span class="dot-run"></span>';
                countRun++;
            } else if (e.etat === "maintenance") {
                emo = "🟡";
            } else if (e.etat === "stop") {
                emo = "🔴";
            } else {
                emo = "⚫";
            }

            htmlEtat += `
                <tr>
                    <td><strong>Presse ${e.presse}</strong></td>
                    <td>
                        <span class="badge status-${etatLower}">
                            ${dot}${emo} ${e.etat.toUpperCase()}
                        </span>
                    </td>
                </tr>`;
        });
        document.getElementById("tableEtat").innerHTML = htmlEtat;
    } catch (e) { 
        console.error("Erreur rafraîchissement Dashboard :", e); 
    }
}


setInterval(refreshMe, 2000);

document.addEventListener("DOMContentLoaded", refreshMe);