export function show3ds(content) {
    // TODO: add loader when iframe is empty
    // TODO: improve iframe centering
    let iframe = document.createElement('iframe');
    css(iframe, {
        width: '100%',
        height: '70vh',
    })

    let s1 = document.createElement('section');
    css(s1, {
        'overflow-y': 'auto'
    });
    s1.append(iframe);

    let d2 = document.createElement('div');
    css(d2, {
        'position': 'relative',
        'z-index': '100000',
        'max-height': '1000px',
        'max-width': '90%',
        'margin': '5% auto',
        'background': '#fff',
    });
    d2.append(s1);

    let d1 = document.createElement('div');
    css(d1, {
        'position': 'fixed',
        'top': '0',
        'left': '0',
        'bottom': '0',
        'right': '0',
        'background': 'rgba(0, 0, 0, 0.8)',
        'z-index': '100000',
    });
    d1.append(d2);

    document.body.append(d1);

    // add iframe source content
    iframe.src = 'data:text/html;charset=utf-8,' + encodeURI(content);
}

function css(el, styles) {
    for (const property in styles) {
        el.style[property] = styles[property];
    }
}