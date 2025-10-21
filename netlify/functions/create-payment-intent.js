// This file runs in a Node.js serverless environment (e.g., Netlify Function)

// The Stripe secret key is securely pulled from Netlify's Environment Variables.
// You MUST set a variable named STRIPE_SECRET_KEY in your Netlify dashboard.
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY); 

exports.handler = async (event, context) => {
  // Security check: Ensure the request is a POST request
  if (event.httpMethod !== 'POST') {
    return { 
      statusCode: 405, 
      body: JSON.stringify({ error: 'Method Not Allowed' }) 
    };
  }

  try {
    // Parse the JSON body sent from the front-end fetch() call
    const { amount, metadata = {} } = JSON.parse(event.body);

    // CRITICAL SERVER-SIDE VALIDATION
    if (typeof amount !== 'number' || amount <= 0) {
      return { 
        statusCode: 400, 
        body: JSON.stringify({ error: 'Invalid or missing amount provided.' }) 
      };
    }

    // Convert the amount from dollars/local currency unit to cents (Stripe's requirement)
    // Use Math.round() to ensure correct integer conversion
    const amountInCents = Math.round(amount * 100);

    // 1. Create the Payment Intent via the Stripe API
    const paymentIntent = await stripe.paymentIntents.create({
      amount: amountInCents,
      currency: 'usd', // Set your currency. 'usd' is standard for US-based law offices.
      // Automatic confirmation (optional, but simplifies the flow):
      automatic_payment_methods: {
        enabled: true,
      },
      // Pass data you want associated with the transaction
      metadata: {
        ...metadata,
        function_call: 'create-payment-intent'
      }
    });

    // 2. Send the client_secret back to the front-end
    // This client_secret is used by Stripe.js to confirm the payment
    return {
      statusCode: 200,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        clientSecret: paymentIntent.client_secret,
      }),
    };

  } catch (error) {
    console.error('Stripe Function Error:', error.message);
    
    // Send a generic error response back to the client
    return {
      statusCode: 500,
      body: JSON.stringify({ 
        error: error.message || 'Failed to create Payment Intent on the server.'
      }),
    };
  }
};
