/**
 * ZKTeco Device Middleware
 * This Node.js service acts as a middleware between the Laravel application
 * and ZKTeco biometric devices. It provides real-time device connectivity
 * checking and can be extended for WebSocket-based live attendance logging.
 * 
 * Usage: node device-middleware.js
 * Then access via: http://localhost:8085
 */

const net = require('net');
const express = require('express');
const cors = require('cors');
const app = express();

app.use(cors());
app.use(express.json());

const PORT = process.env.DEVICE_MIDDLEWARE_PORT || 8085;

// Device connection pool
const deviceConnections = new Map();

/**
 * Check if a ZKTeco device is reachable
 * @param {string} ip - Device IP address
 * @param {number} port - Device port (default: 4370)
 * @returns {Promise<boolean>} - Connection status
 */
function checkDeviceConnectivity(ip, port = 4370) {
    return new Promise((resolve) => {
        const socket = new net.Socket();
        const timeout = 5000; // 5 seconds timeout

        // Set timeout
        const timer = setTimeout(() => {
            socket.destroy();
            resolve(false);
        }, timeout);

        socket.on('connect', () => {
            clearTimeout(timer);
            socket.destroy();
            resolve(true);
        });

        socket.on('error', () => {
            clearTimeout(timer);
            resolve(false);
        });

        socket.connect(port, ip);
    });
}

/**
 * Get detailed device information
 * @param {string} ip - Device IP address
 * @param {number} port - Device port
 * @returns {Promise<object>} - Device info
 */
async function getDeviceInfo(ip, port = 4370) {
    const isConnected = await checkDeviceConnectivity(ip, port);
    
    if (!isConnected) {
        return {
            connected: false,
            message: 'Device is not reachable'
        };
    }

    return {
        connected: true,
        ip: ip,
        port: port,
        message: 'Device is connected and ready',
        // Note: For full SDK integration, you would use the node-zkteco package
        // This is a basic connectivity check
        timestamp: new Date().toISOString()
    };
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
        activeDevices: deviceConnections.size
    });
});

// Check device connectivity
app.post('/device/check', async (req, res) => {
    try {
        const { ip, port = 4370 } = req.body;

        if (!ip) {
            return res.status(400).json({
                success: false,
                message: 'IP address is required'
            });
        }

        console.log(`Checking device connectivity: ${ip}:${port}`);
        const deviceInfo = await getDeviceInfo(ip, port);

        res.json({
            success: true,
            data: deviceInfo
        });
    } catch (error) {
        console.error('Error checking device:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Check multiple devices at once
app.post('/device/check-multiple', async (req, res) => {
    try {
        const { devices } = req.body;

        if (!devices || !Array.isArray(devices)) {
            return res.status(400).json({
                success: false,
                message: 'Devices array is required'
            });
        }

        console.log(`Checking ${devices.length} devices`);
        
        const results = await Promise.all(
            devices.map(async (device) => {
                const ip = device.ip;
                const port = device.port || 4370;
                const info = await getDeviceInfo(ip, port);
                return {
                    ip,
                    port,
                    ...info
                };
            })
        );

        res.json({
            success: true,
            data: results
        });
    } catch (error) {
        console.error('Error checking multiple devices:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Get connection status for all tracked devices
app.get('/devices/status', (req, res) => {
    const status = {};
    deviceConnections.forEach((value, key) => {
        status[key] = value;
    });

    res.json({
        success: true,
        data: status
    });
});

// Start the middleware server
app.listen(PORT, () => {
    console.log(`ZKTeco Device Middleware running on port ${PORT}`);
    console.log(`Access at: http://localhost:${PORT}`);
    console.log('Endpoints:');
    console.log('  GET  /health - Health check');
    console.log('  POST /device/check - Check single device');
    console.log('  POST /device/check-multiple - Check multiple devices');
    console.log('  GET  /devices/status - Get all tracked device statuses');
});

module.exports = app;
