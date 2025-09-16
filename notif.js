function chargerNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const conteneur = document.getElementById('notifications');
            conteneur.innerHTML = '';

            if (data.length === 0) {
                conteneur.innerHTML = '<li class="dropdown-item text-muted">Aucune mission à traiter</li>';
            } else {
                data.forEach(m => {
                    const item = document.createElement('li');
                    item.className = 'dropdown-item';
                    item.innerHTML = `📝 Mission <strong>M-${String(m.id).padStart(4, '0')}</strong> - ${m.titre}`;
                    conteneur.appendChild(item);
                });
            }

            const badge = document.getElementById('notif-count');
            badge.textContent = data.length;
            badge.style.display = data.length > 0 ? 'inline-block' : 'none';
        })
        .catch(error => console.error('Erreur notif :', error));
}

setInterval(chargerNotifications, 10000);
window.addEventListener('DOMContentLoaded', chargerNotifications);
