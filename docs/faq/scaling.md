---
title: Benchmarks
order: 2
---

# Benchmarks

Of course, this is not a question with an easy answer as your mileage may vary. But with the appropriate server-side configuration your WebSocket server can easily hold a **lot** of concurrent connections.

This is an example benchmark that was done on the smallest Digital Ocean droplet, that also had a couple of other Laravel projects running. On this specific server, the maximum number of **concurrent** connections ended up being ~15,000.

![Benchmark](/img/simultaneous_users.png)

Here is another benchmark that was run on a 2GB Digital Ocean droplet with 2 CPUs. The maximum number of **concurrent** connections on this server setup is nearly 60,000.

![Benchmark](/img/simultaneous_users_2gb.png)

Make sure to take a look at the [Deployment Tips](/docs/laravel-websockets/faq/deploying) to find out how to improve your specific setup.

# Horizontal Scaling

When deploying to multi-node environments, you will notice that the server won't behave correctly. Check [Horizontal Scaling](../horizontal-scaling/getting-started.md) section.
