<!DOCTYPE html>
<html lang="en"> 
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Quality Registration Services</title>

  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- JS Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
  :root {
    --primary-color: #1aa238;     
    --secondary-color: #0d6620;   
    --card-bg: #ffffff;
    --hover-glow: rgba(26, 162, 56, 0.2);
    --logo-bg: #f9f9f9;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
  }

  /* Sidebar gradient like audit cards */
  .sidebar {
      min-height: 100vh;
      width: 250px;
      background: linear-gradient(135deg, green 0%, #3b3737 70%, gold 100%);
      color: white;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 18px rgba(0,0,0,0.25);
      border-radius: 0 16px 16px 0;
  }

 .sidebar a {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 4px;
    transition: all 0.3s ease-in-out;
}

.sidebar a i {
    margin-right: 10px;
    color: limegreen; /* default icon color */
    font-size: 20px;
}

.sidebar a:hover {
    background: rgba(255, 215, 0, 0.2);
    transform: translateX(5px);
}

.sidebar a:hover i {
    color: gold; /* icon color on hover */
}

.sidebar a.active {
    background: limegreen;
    font-weight: 600;
    box-shadow: inset 4px 0 0 gold;
}

.sidebar a.active i {
    color: white; /* icon visible on active button */
}


  /* Accent stripe at the bottom */
  .sidebar-accent {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background-color: gold;
      border-bottom-right-radius: 16px;
  }

  /* Sidebar Collapsed */
  .sidebar-collapsed {
      width: 70px;
  }
  .sidebar-collapsed a span {
      display: none;
  }

  /* Sidebar Logo with visible background */
  .sidebar-logo {
      padding: 10px;
      text-align: center;
      background: rgba(255, 255, 255, 0.85);
      border-radius: 8px;
      margin-bottom: 15px;
  }
  .sidebar-logo img {
      max-width: 140px;
      height: auto;
  }

  /* Navbar */
  .navbar {
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  }
  .navbar-brand {
      color: var(--primary-color);
      font-weight: 600;
  }

  /* Content */
  .content {
      transition: margin-left 0.3s;
      padding: 20px;
  }

  /* Buttons */
  .btn-brand {
      background-color: var(--primary-color);
      border: none;
      color: #fff;
  }
  .btn-brand:hover {
      background-color: var(--secondary-color);
      color: #fff;
  }
  .btn-outline-brand {
      border: 1px solid var(--primary-color);
      color: var(--primary-color);
  }
  .btn-outline-brand:hover {
      background-color: var(--primary-color);
      color: #fff;
  }

  /* Tables */
  #clientsTable thead {
      background-color: var(--primary-color);
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
      .sidebar {
          position: fixed;
          z-index: 1000;
          left: -250px;
          top: 0;
      }
      .sidebar.show {
          left: 0;
      }
      .content {
          margin-left: 0 !important;
      }
  }

  .custom-btn {
      background: linear-gradient(45deg, green, black, yellow);
      color: white;
      border: none;
      padding: 10px 20px;
      font-weight: bold;
      transition: transform 0.2s;
  }
  .custom-btn:hover {
      transform: scale(1.05);
  }

  .heading-bg{
    /*background-color: #008000;*/
    color: white;
    background: linear-gradient(45deg, #1fb51f, #060606, #dada80);
  }
  </style>
</head>
<body>

  @yield('content')

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggleSidebar');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
      } else {
        sidebar.classList.toggle('sidebar-collapsed');
      }
    });
  }
</script>


<script>
    // Pass userId to JS
    window.userId = {{ auth()->id() }};
</script>

<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Echo) {
            window.Echo.private(`App.Models.User.${window.userId}`)
                .notification((notification) => {
                    console.log("📩 New Notification:", notification);
                    // Display popup
                    alert(notification.message);

                    // Optional: Add to dropdown
                    const dropdown = document.querySelector('#notification-dropdown');
                    if(dropdown) {
                        const item = document.createElement('li');
                        item.textContent = notification.message;
                        dropdown.prepend(item);
                    }
                });
        } else {
            console.error('Echo not initialized!');
        }
    });
</script>




</body>
</html>
