// Gestion des cookies pour les favoris
// Fonction pour obtenir les cookies sous forme d'objet
function getCookies() {
  const cookies = {};
  document.cookie.split(';').forEach(cookie => {
    const [name, value] = cookie.split('=');
    cookies[name.trim()] = value ? decodeURIComponent(value.trim()) : '';
  });
  console.log('Contenu de cookies:', cookies); // Affiche le contenu de la constante cookies
  return cookies;
}

// Fonction pour enregistrer des cookies
function setCookie(name, value, days) {
  const expires = new Date();
  expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
  document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/`;
}

// Fonction pour ajouter ou retirer un ID de la liste dans le cookie
function toggleIdInCookie(id) {
  const cookies = getCookies();
  let ids = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];

  if (ids.includes(id)) {
    // Retirer l'ID du cookie
    ids = ids.filter(item => item !== id);
    setCookie('clicked_ids', JSON.stringify(ids), 7);
    console.log(`cookie desactive = ` + id + ' A la liste = ' + JSON.stringify(ids));
    return false; // Indique que l'élément a été désactivé
  } else {
    // Ajouter l'ID au cookie
    ids.push(id);
    setCookie('clicked_ids', JSON.stringify(ids), 7);
    console.log(`cookie Ajouter l'ID au cookie = ` + id + ' A la liste = ' + JSON.stringify(ids));
    return true; // Indique que l'élément a été activé
  }
}

// Fonction pour marquer ou démarrer les éléments en fonction des cookies
function markItems() {
  const cookies = getCookies();
  const ids = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];
  ids.forEach(id => {
    const item = document.getElementById(id);
    if (item) {
      item.querySelector('i').classList.add('heart-active');
      console.log(`cookie markItems= ` + id);
    }
  });
}

// Gestion des clics sur les éléments avec la classe 'track-click'
// Marquer les éléments au chargement de la page
// Objet pour stocker les références des gestionnaires d'événements
const eventListenersMap = new Map();

// Fonction pour ajouter des gestionnaires d'événements
function markItemsClickToggle() {
  // Écouter à la fois les li.track-click et span.badge.track-click, a.badge.track-click
  document.querySelectorAll('li.track-click, span.track-click, a.track-click').forEach(element => {
    // Définir une fonction de gestion d'événement pour le 'click'
    const clickHandler = () => {
      const id = element.id;
      const isActive = toggleIdInCookie(id);
      const icon = element.querySelector('i');
      if (icon) {
        if (isActive) {
          icon.classList.add('heart-active');
          icon.classList.add('heart-clicked');
          console.log(`click cookie toggle active 1`);
          // Retirer la classe 'heart-clicked' après l'animation
          setTimeout(() => {
            icon.classList.remove('heart-clicked');
          }, 300); // Correspond à la durée de l'animation
        } else {
          icon.classList.remove('heart-active');
          console.log(`click cookie toggle active 2`);
        }
      }
      
      // Appeler ces fonctions si elles existent
      if (typeof updateFavorisCountListView === 'function') {
        updateFavorisCountListView();
      }
      if (typeof updateListWithFavorisGridView === 'function') {
        updateListWithFavorisGridView();
      }
      console.log(`ID ${id} ${isActive ? 'ajouté' : 'retiré'} du cookie.`);
    };
    // Ajouter l'événement 'click' à l'élément
    element.addEventListener('click', clickHandler);
    // Stocker la référence du gestionnaire d'événement pour cet élément
    eventListenersMap.set(element, clickHandler);
  });
  console.log('Contenu de eventListenersMap après l\'ajout:', Array.from(eventListenersMap.entries())); // Affiche le contenu de la map
}

// Fonction pour supprimer tous les gestionnaires d'événements des éléments 'li.track-click'
function removeAllClickListeners() {
  eventListenersMap.forEach((clickHandler, li) => {
    if (clickHandler) {
      li.removeEventListener('click', clickHandler); // Supprimer le gestionnaire d'événement
      eventListenersMap.delete(li); // Supprimer la référence de la Map
    }
  });
  console.log('Delete Contenu de eventListenersMap:', Array.from(eventListenersMap.entries())); // Affiche le contenu de la map
}

function cookieMarkItemAndClickToggle() {
  setTimeout(() => {
    removeAllClickListeners();
    markItemsClickToggle()
    markItems();
  }, 1500); // Correspond à la durée de l'animation
}

// Gestion des cookies pour la vue (grille/liste)
// Fonction pour créer un cookie
function setCookieGridViewButton(name, value, days) {
  let date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  let expires = "expires=" + date.toUTCString();
  document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

// Fonction pour supprimer un cookie
function deleteCookieGridViewButton(name) {
  document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}
