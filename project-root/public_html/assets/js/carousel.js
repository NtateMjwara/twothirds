document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('.featured-carousel-wrap').forEach(function (wrap) {
        var track = wrap.querySelector('.featured-carousel');
        var prev = wrap.querySelector('.carousel-prev');
        var next = wrap.querySelector('.carousel-next');
        if (!track) return;

        function scrollByCard(direction) {
            var card = track.querySelector('.card');
            var amount = card ? card.getBoundingClientRect().width + 16 : 260;
            track.scrollBy({ left: direction * amount, behavior: reduceMotion ? 'auto' : 'smooth' });
        }

        if (prev) prev.addEventListener('click', function () { scrollByCard(-1); });
        if (next) next.addEventListener('click', function () { scrollByCard(1); });
    });
});
