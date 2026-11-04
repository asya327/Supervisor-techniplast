function updateDate() {
    const dateElement = document.getElementById("currentdate");
    if (dateElement) {
        dateElement.innerHTML = new Date().toLocaleString();
    }
}
updateDate();
setInterval(updateDate, 1000);

console.log("Système de supervision initialisé.");
function managePython(action) {
        console.log("Action demandée :", action);
        const status = document.getElementById('debug-msg');
        status.innerText = "Traitement en cours...";

        fetch('control_python.php?action=' + action)
            .then(response => response.text())
            .then(data => {
                console.log("Réponse du serveur :", data);
                status.innerText = "Serveur : " + data;
            })
            .catch(err => {
                console.error("Erreur Fetch :", err);
                status.innerText = "Erreur de connexion au serveur.";
            });
    }