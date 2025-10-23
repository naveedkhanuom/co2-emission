import './bootstrap';

window.Echo.channel('follow-ups')
    .listen('.FollowUpDue', (e) => {
        console.log("📢 Event received:", e);

        // Show toast notification
        toastr.success(`New follow-up for ${e.clientName} on ${e.date}: ${e.notes}`);
    });
