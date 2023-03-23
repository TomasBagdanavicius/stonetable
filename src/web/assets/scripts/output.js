'use strict';

openInPlayground.addEventListener('click', e => {

    e.preventDefault();

    fetch(e.target)
        .then(response => response.json())
        .then(payload => {
            open(payload.data.IdeUri, '_self')
        })
        .catch(error => console.log(error));
});