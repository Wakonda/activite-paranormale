(function() {
  function BackToTop(options) {
    var defaults = {
      "amountScrolled": 200
    };

    options = Object.assign({}, defaults, options);

    // Créez le bouton "Retour en haut de la page"
    var backToTopButton = document.createElement('a');
    backToTopButton.href = "#";
    backToTopButton.className = "back-to-top";
    backToTopButton.textContent = "Back to Top";
    document.body.insertBefore(backToTopButton, document.body.firstChild);

    window.addEventListener('scroll', function() {
      if (window.pageYOffset > options.amountScrolled) {
        backToTopButton.style.display = 'block';
      } else {
        backToTopButton.style.display = 'none';
      }
    });

    backToTopButton.addEventListener('click', function(event) {
      event.preventDefault();
      scrollToTop(700);
    });

    function scrollToTop(duration) {
      var start = window.pageYOffset;
      var startTime = 'now' in window.performance ? performance.now() : new Date().getTime();

      function animateScroll(currentTime) {
        var timeElapsed = currentTime - startTime;
        var easeInOutQuad = function(t, b, c, d) {
          t /= d / 2;
          if (t < 1) return c / 2 * t * t + b;
          t--;
          return -c / 2 * (t * (t - 2) - 1) + b;
        };
        window.scrollTo(0, easeInOutQuad(timeElapsed, start, -start, duration));
        if (timeElapsed < duration) requestAnimationFrame(animateScroll);
        else window.scrollTo(0, 0);
      }

      requestAnimationFrame(animateScroll);
    }
  }

  window.BackToTop = BackToTop;
})();
