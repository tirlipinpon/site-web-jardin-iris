// Gestion des cookies pour les favoris
// Fonction pour obtenir les cookies sous forme d'objet
function getCookies() {
  const cookies = {};
  document.cookie.split(';').forEach(cookie => {
    const [name, value] = cookie.split('=');
    cookies[name.trim()] = value ? decodeURIComponent(value.trim()) : '';
  });
  
  // Debug du cookie clicked_ids
  if (cookies['clicked_ids']) {
    const ids = cookies['clicked_ids'];
    console.log(`[Cookie] clicked_ids: "${ids.substring(0, 100)}${ids.length > 100 ? '...' : ''}"`);
  } else {
    console.log('[Cookie] clicked_ids: vide');
  }
  
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
  // Ne pas permettre l'ajout d'URLs YouTube ou de chaînes non numériques
  const isNumericId = !isNaN(parseInt(id)) && parseInt(id) > 0 && id.indexOf('http') === -1;
  if (!isNumericId) {
    console.log('Ignoré - ce n\'est pas un ID d\'article valide:', id);
    return false;
  }
  
  const cookies = getCookies();
  let ids = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];

  // Nettoyer le tableau pour ne garder que les IDs numériques valides
  ids = ids.filter(item => {
    const isNum = !isNaN(parseInt(item)) && parseInt(item) > 0 && item.indexOf('http') === -1;
    return isNum;
  });

  let wasChanged = false;
  if (ids.includes(id)) {
    // Retirer l'ID du cookie
    ids = ids.filter(item => item !== id);
    wasChanged = true;
  } else {
    // Ajouter l'ID au cookie
    ids.push(id);
    wasChanged = true;
  }
  
  // Toujours sauvegarder le tableau nettoyé
  setCookie('clicked_ids', JSON.stringify(ids), 7);
  
  if (wasChanged) {
    if (ids.includes(id)) {
      console.log(`+ Favori ajouté: ${id}`);
      return true; // Indique que l'élément a été activé
    } else {
      console.log(`- Favori retiré: ${id}`);
      return false; // Indique que l'élément a été désactivé
    }
  }
  
  return false;
}

// Fonction pour marquer ou démarrer les éléments en fonction des cookies
function markItems() {
  const cookies = getCookies();
  const ids = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];
  console.log(`[MarkItems] ${ids.length} favoris à marquer`);
  
  ids.forEach(id => {
    // Convertir l'ID en string pour le comparer avec les IDs des éléments
    const idStr = String(id);
    // N'afficher le cœur que pour les IDs d'articles (nombres), pas pour les URLs YouTube
    const isNumericId = !isNaN(parseInt(idStr)) && parseInt(idStr) > 0 && idStr.indexOf('http') === -1;
    
    if (isNumericId) {
      // Chercher l'élément par ID (en string)
      const item = document.getElementById(idStr);
      
      if (item) {
        // Chercher spécifiquement l'icône fa-heart (le cœur), pas les autres badges
        const icon = item.querySelector('i.fa-heart');
        if (icon) {
          icon.classList.add('heart-active');
          // FORCER le style en inline pour s'assurer que ça fonctionne
          icon.style.color = 'green';
          icon.style.fontWeight = '700';
          console.log(`[MarkItems] ✓ ${idStr}`);
        }
      }
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
      
      // Ne gérer que les IDs d'articles (nombres), pas les URLs YouTube
      const isNumericId = !isNaN(parseInt(id)) && parseInt(id) > 0 && id.indexOf('http') === -1;
      
      // Si c'est une URL YouTube, ne rien faire pour les favoris
      if (!isNumericId) {
        return; // Les badges YouTube ne doivent pas être ajoutés aux favoris
      }
      
      const isActive = toggleIdInCookie(id);
      const icon = element.querySelector('i');
      if (icon) {
        if (isActive) {
          icon.classList.add('heart-active');
          icon.style.color = 'green';
          icon.style.fontWeight = '700';
          icon.classList.add('heart-clicked');
          // Retirer la classe 'heart-clicked' après l'animation
          setTimeout(() => {
            icon.classList.remove('heart-clicked');
          }, 300); // Correspond à la durée de l'animation
        } else {
          icon.classList.remove('heart-active');
          icon.style.color = ''; // Retirer le style inline
          icon.style.fontWeight = ''; // Retirer le style inline
        }
      }
      
      // Appeler ces fonctions si elles existent
      if (typeof updateFavorisCountListView === 'function') {
        updateFavorisCountListView();
      }
      if (typeof updateListWithFavorisGridView === 'function') {
        updateListWithFavorisGridView();
      }
      
      // Si on est sur "Mes Favoris", recharger les données
      if (typeof currentSelectedCategory !== 'undefined' && currentSelectedCategory === 'MES_FAVORIS') {
        if (typeof fetchDataInfinite === 'function') {
          fetchDataInfinite('created_at');
        }
      }
      
      // Mettre à jour le bouton "Mes Favoris" en recréant les catégories
      setTimeout(() => {
        // Attendre un peu pour que le cookie soit bien sauvegardé
        setTimeout(() => {
          // Récupérer les favoris propres (sans URLs YouTube)
          const cookies = getCookies();
          const favorisIds = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];
          const validFavorisIds = favorisIds.filter(id => {
            return !isNaN(parseInt(id)) && parseInt(id) > 0 && id.indexOf('http') === -1;
          });
          const validFavorisCount = validFavorisIds.length;
          
          // Mettre à jour ou recréer le bouton "Mes Favoris"
          const favoritesButton = document.querySelector('.category-btn i.fa-heart')?.parentElement;
          
          if (validFavorisCount > 0) {
            // Si le bouton existe, mettre à jour le compteur
            if (favoritesButton) {
              const badge = favoritesButton.querySelector('.category-badge');
              if (badge) {
                badge.textContent = validFavorisCount;
              }
            } else {
              // Si le bouton n'existe pas mais qu'on a des favoris, recréer les catégories
              if (typeof window.createCategoryButtons === 'function' && 
                  typeof window.cachedCategories !== 'undefined' && 
                  typeof window.cachedCategoryCounts !== 'undefined') {
                // Recréer tous les boutons de catégories avec le bon compteur
                window.createCategoryButtons(window.cachedCategories, window.cachedCategoryCounts);
                
                // Réactiver "Tous" par défaut après recréation
                setTimeout(() => {
                  document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                  const allButton = document.querySelector('.category-btn:first-child');
                  if (allButton) {
                    allButton.classList.add('active');
                  }
                }, 50);
              }
            }
          } else {
            // Si plus de favoris, supprimer le bouton
            if (favoritesButton) {
              favoritesButton.remove();
              if (typeof currentSelectedCategory !== 'undefined' && currentSelectedCategory === 'MES_FAVORIS') {
                currentSelectedCategory = null;
                if (typeof fetchDataInfinite === 'function') {
                  fetchDataInfinite('created_at');
                }
              }
            }
          }
        }, 100);
      }, 100);
      
      console.log(`[Click] ${id} ${isActive ? 'ajouté' : 'retiré'}`);
    };
    // Ajouter l'événement 'click' à l'élément
    element.addEventListener('click', clickHandler);
    // Stocker la référence du gestionnaire d'événement pour cet élément
    eventListenersMap.set(element, clickHandler);
  });
  console.log(`[Listeners] ${eventListenersMap.size} écouteurs ajoutés`);
}

// Fonction pour supprimer tous les gestionnaires d'événements des éléments 'li.track-click'
function removeAllClickListeners() {
  eventListenersMap.forEach((clickHandler, li) => {
    if (clickHandler) {
      li.removeEventListener('click', clickHandler); // Supprimer le gestionnaire d'événement
      eventListenersMap.delete(li); // Supprimer la référence de la Map
    }
  });
  console.log(`[Listeners] ${eventListenersMap.size} écouteurs supprimés`);
}

function cookieMarkItemAndClickToggle() {
  setTimeout(() => {
    removeAllClickListeners();
    markItemsClickToggle()
    markItems();
  }, 100); // Délai réduit pour que ce soit plus rapide
}

// Version sans délai pour appeler immédiatement
function markItemsImmediate() {
  removeAllClickListeners(); // Nettoyer les anciens listeners AVANT d'en ajouter de nouveaux
  markItems();
  markItemsClickToggle();
}

// Fonction de debug complètement isolée pour trouver les cœurs
function debugMarkFavorites() {
  console.log('[DebugFav] Démarrage');
  
  const cookies = getCookies();
  const ids = cookies['clicked_ids'] ? JSON.parse(cookies['clicked_ids']) : [];
  
  const allTrackClickSpans = document.querySelectorAll('span.track-click');
  console.log(`[DebugFav] ${allTrackClickSpans.length} spans track-click, ${ids.length} favoris`);
  
  let countMarked = 0;
  ids.forEach(id => {
    const idStr = String(id);
    const span = document.querySelector(`span.track-click[id="${idStr}"]`);
    
    if (span) {
      const hearts = span.querySelectorAll('i.fa-heart');
      
      hearts.forEach((heart, index) => {
        if (heart.classList.contains('fa-heart')) {
          heart.classList.add('heart-active');
          heart.style.color = 'green';
          heart.style.fontWeight = '700';
          countMarked++;
        }
      });
    }
  });
  
  console.log(`[DebugFav] ${countMarked} cœurs marqués en vert`);
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
