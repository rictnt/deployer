var app = require('http').createServer(handler);
var io  = require('socket.io')(app);
var jwt = require('jsonwebtoken');

require('dotenv').load();

var debug = (process.env.APP_DEBUG === 'true' || process.env.APP_DEBUG === true);

var Redis = require('ioredis');
var redis = new Redis();

app.listen(parseInt(process.env.SOCKET_PORT), function() {
    if (debug) {
        console.log('Server is running!');
    }
});

function handler(req, res) {
    res.writeHead(200);
    res.end('');
}

// Middleware to check the JWT
io.use(function(socket, next) {
    var decoded;

    try {
        decoded = jwt.verify(socket.handshake.query.jwt, process.env.JWT_SECRET);
    } catch (err) {
        console.error(err);
        next(new Error('Invalid token!'));
    }

    if (decoded) {
        // everything went fine - save userId as property of given connection instance
        socket.userId = decoded.data.userId;
        next();
    } else {
        // invalid token - terminate the connection
        next(new Error('Invalid token!'));
    }
});

io.on('connection', function(socket) {
    if (debug) {
        console.log('connection');
    }
});

redis.psubscribe('*', function(err, count) {
    if (debug) {
        console.log('psubscribe');
    }
});

redis.on('pmessage', function(subscribed, channel, message) {
    message = JSON.parse(message);

    if (debug) {
        console.log('Message received from event ' + message.event + ' to channel ' + channel);
    }

    io.emit(channel + ':' + message.event, message.data);
});
