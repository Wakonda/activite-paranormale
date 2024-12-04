class Marquee {
    constructor(element, options) {
        this.element = element;
        this.options = Object.assign({
            yScroll: "top",
            showSpeed: 850,
            scrollSpeed: 12,
            pauseSpeed: 5000,
            pauseOnHover: true,
            loop: -1,
            cssShowing: "marquee-showing",
            init: null,
            beforeshow: null,
            show: null,
            aftershow: null
        }, options);
        this.lis = Array.from(this.element.querySelectorAll("li"));
        this.current = -1;
        this.hardPaused = false;
        this.paused = false;
        this.loopCount = 0;

        if (this.options.init) this.options.init(this.element, this.options);
        this.initEventListeners();
        this.showNext();
    }

    pause() {
        this.hardPaused = true;
        this.paused = true;
    }

    resume() {
        this.hardPaused = false;
        this.paused = false;
        this.scroll(this.getCurrentLi());
    }

    next() {
        this.resetStyles();
        this.showNext();
        this.pause();
    }

    previous() {
        this.resetStyles();
        this.showPrevious();
        this.resume();
    }

    update() {
        const itemCount = this.lis.length;
        this.lis = Array.from(this.element.querySelectorAll("> li"));
        if (itemCount <= 1) this.resume();
    }

    resetStyles() {
        this.lis.forEach(li => {
            li.classList.remove(this.options.cssShowing);
            li.style = "";
        });
    }

    show(i) {
        if (this.isAnyShowing()) return;

        const li = this.lis[i];
        if (this.options.beforeshow) this.options.beforeshow(this.element, li);

        li.style.top = this.options.yScroll === "top" ? `-${li.offsetHeight}px` : `${li.offsetHeight}px`;
        li.style.left = "0";
        li.classList.add(this.options.cssShowing);

        this.animate(li, { top: "0px" }, this.options.showSpeed, () => {
            if (this.options.show) this.options.show(this.element, li);
            this.scroll(li);
        });
    }

    scroll(li, delay = this.options.pauseSpeed) {//console.log("ooo");
        if (this.paused) return;

        setTimeout(() => {
            if (this.doScroll(li)) {
                const width = li.offsetWidth;
                const endPos = -width;
                const curPos = parseInt(getComputedStyle(li).left, 10) || 0;

                this.animate(li, { left: `${endPos}px` }, ((width + curPos) * this.options.scrollSpeed), () => {
                    this.finish(li);
                });
            } else if (this.lis.length > 1) {
                const direction = this.options.yScroll === "top" ? "+" : "-";
                const height = this.element.clientHeight;

                this.animate(li, { top: `${direction}${height}px` }, this.options.showSpeed, () => {
                    this.finish(li);
                });
            }
        }, delay);
    }

    finish(li) {
        if (this.options.aftershow) this.options.aftershow(this.element, li);
        li.classList.remove(this.options.cssShowing);
        this.showNext();
    }

    doScroll(li) {
        return li.offsetWidth > this.element.clientWidth;
    }

    showNext() {
        this.current = (this.current + 1) % this.lis.length;
        if (this.current === 0 && !isNaN(this.options.loop) && this.options.loop > 0) {
            this.loopCount++;
            if (this.loopCount >= this.options.loop) return;
        }
        this.show(this.current);
    }

    showPrevious() {
        this.current = (this.current - 1 + this.lis.length) % this.lis.length;
        this.show(this.current);
    }

    isAnyShowing() {
        return this.lis.some(li => li.classList.contains(this.options.cssShowing));
    }

    getCurrentLi() {
        return this.lis.find(li => li.classList.contains(this.options.cssShowing));
    }

    initEventListeners() {
        if (this.options.pauseOnHover) {
            this.element.addEventListener("mouseenter", () => {
                if (!this.hardPaused) this.pause();
            });
            this.element.addEventListener("mouseleave", () => {
                if (!this.hardPaused) this.resume();
            });
        }
    }

    animate(element, styles, duration, callback) {
        const start = performance.now();
        const initialStyles = {};

        for (const prop in styles) {
            initialStyles[prop] = parseFloat(getComputedStyle(element)[prop]) || 0;
        }

        const step = (timestamp) => {
            const progress = Math.min((timestamp - start) / duration, 1);
            for (const prop in styles) {
                const initial = initialStyles[prop];
                const target = parseFloat(styles[prop]);
                const value = initial + (target - initial) * progress;
                element.style[prop] = `${value}px`;
            }
            if (progress < 1) {
                requestAnimationFrame(step);
            } else if (callback) {
                callback();
            }
        };

        requestAnimationFrame(step);
    }
}

/* EXAMPLE */
/*
		document.addEventListener("DOMContentLoaded", function () {
			const marquee1 = new Marquee(document.getElementById("marquee1"));

			document.querySelector(".pause-marquee").addEventListener("click", function () {
				marquee1.pause();
				document.querySelector(".resume-marquee").classList.remove("d-none");
				document.querySelector(".pause-marquee").classList.add("d-none");
			});

			document.querySelector(".resume-marquee").addEventListener("click", function () {
				marquee1.resume();
				document.querySelector(".resume-marquee").classList.add("d-none");
				document.querySelector(".pause-marquee").classList.remove("d-none");
			});

			document.querySelector(".next-marquee").addEventListener("click", function () {
				marquee1.next();
				document.querySelector(".pause-marquee").click();
			});

			document.querySelector(".previous-marquee").addEventListener("click", function () {
				marquee1.previous();
				document.querySelector(".pause-marquee").click();
			});
		});
*/