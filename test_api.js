const axios = require('axios');

async function test() {
    const base = 'http://localhost:3000/api';
    try {
        console.log('Testing Login...');
        const loginRes = await axios.post(`${base}/auth/login`, {
            login: 'admin',
            password: 'admin'
        });
        const token = loginRes.data.token;
        const config = { headers: { Authorization: `Bearer ${token}` } };
        console.log('Login successful.');

        console.log('Testing Get Work Types...');
        const wtRes = await axios.get(`${base}/admin/worktypes`, config);
        const workTypeId = wtRes.data[0].id;
        console.log(`Work Type ID: ${workTypeId}`);

        console.log('Testing Create Request...');
        const reqRes = await axios.post(`${base}/requests`, {
            work_type_id: workTypeId,
            priority: 'high',
            location: 'Office 101',
            description: 'Test problem'
        }, config);
        const requestId = reqRes.data.id;
        console.log(`Request Created with ID: ${requestId}`);

        console.log('Testing Get My Requests...');
        const myReqRes = await axios.get(`${base}/requests/my`, config);
        console.log(`My requests count: ${myReqRes.data.length}`);

        console.log('Testing Change Status...');
        await axios.patch(`${base}/requests/${requestId}/status`, {
            status: 'in_progress',
            comment: 'Started working'
        }, config);
        console.log('Status changed to in_progress');

        console.log('Testing Get History...');
        const histRes = await axios.get(`${base}/requests/${requestId}/history`, config);
        console.log(`History entries: ${histRes.data.length}`);

        console.log('All API tests passed!');
    } catch (error) {
        console.error('Test failed:', error.response ? error.response.data : error.message);
        process.exit(1);
    }
}

test();
