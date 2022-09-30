const https = require('http');

let time1 = new Date().getTime();

let max_loops = 500;
let counter = 0;
for (let i = 1; i <= max_loops; i++) {
    https.get('http://127.0.0.1:8080', res => {
        let data = [];
        /*
        const headerDate = res.headers && res.headers.date ? res.headers.date : 'no response date';
        console.log('Status Code:', res.statusCode);
        console.log('Date in Response header:', headerDate);
        */

        res.on('data', chunk => {
            data.push(chunk);
        });

        res.on('end', () => {
            //console.log('Response ended: ');
            //console.log(Buffer.concat(data).toString());
        });

        counter++;

        if (counter === max_loops) {
            let time2 = new Date().getTime();

            let lapsed = time2 - time1;

            console.log('counter = ' + counter);
            console.log('lapsed = ' + lapsed + 'ms');
        }
    }).on('error', err => {
        console.log('Error: ', err.message);
        counter++;
    });
}
