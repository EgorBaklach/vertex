mention = container => values => ((tribute) => tribute.attach(container))(new Tribute({trigger: '{', menuContainer: container.parentNode, values}));

for(let container of document.querySelectorAll(".js-zoomer .fi-fo-file-upload"))
{
    container.onmousemove = event =>
    {
        let offsetX = event.offsetX || event.touches?.[0].pageX || 0, offsetY = event.offsetY || event.touches?.[0].pageY || 0, target = event.currentTarget;

        target.style.backgroundPosition = offsetX / target.offsetWidth * 100 + '% ' + offsetY / target.offsetHeight * 100 + '%';
    };

    container.onmouseleave = event => event.currentTarget.removeAttribute('style');
}

