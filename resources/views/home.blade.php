@extends('layouts.app')

@section('content')
@include('layouts.sidebar')
<br>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

  :root {
    --primary-color: #1aa238;
    --secondary-color: #0d6620;
    --card-bg: #ffffff;
    --hover-glow: rgba(26, 162, 56, 0.2);
    --logo-bg: #f9f9f9;
  }

  body {
    background-color: #f4f6f9;
    font-family: 'Poppins', sans-serif;
  }

  .dashboard-header {
    text-align: center;
    margin-bottom: 30px;
  }

  .dashboard-header h2 {
    font-weight: 700;
    color: var(--primary-color);
  }

  .dashboard-header p {
    color: #666;
    font-size: 1rem;
  }

  .company-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 10px 15px 40px;
  }

  .company-card {
    background: var(--card-bg);
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    transition: all 0.4s ease;
    padding: 25px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    cursor: pointer;
    animation: fadeIn 0.8s ease forwards;
  }

  .company-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 15px 30px var(--hover-glow);
    border-color: var(--primary-color);
  }

  .company-card::before {
    content: '';
    position: absolute;
    top: -30%;
    left: -30%;
    width: 160%;
    height: 160%;
    background: radial-gradient(circle at top left, var(--primary-color), transparent 70%);
    opacity: 0.05;
    z-index: 0;
  }

  .company-logo {
    width: 120px;
    background-color: var(--logo-bg);
    border-radius: 14px;
    padding: 10px;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
  }

  .company-logo:hover {
    transform: scale(1.1) rotate(5deg);
  }

  .company-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-color);
    position: relative;
    z-index: 1;
    margin-bottom: 6px;
  }

  .company-desc {
    font-size: 0.9rem;
    color: #666;
    z-index: 1;
    position: relative;
  }

  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 576px) {
    .company-logo {
      width: 80px;
      height: 80px;
    }

    .company-name {
      font-size: 1rem;
    }
  }


    .audit-card {
    background: linear-gradient(135deg, green 0%, #3b3737 70%, gold 5%);
    border-radius: 16px;
    padding: 24px 20px;
    width: 260px;
    text-align: center;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25);
    color: white;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
    }

    .audit-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.35);
    }

    .audit-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 16px;
        font-size: 18px;
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    .audit-header i {
        font-size: 24px;
        color: limegreen; /* dominant green icon */
    }

    .audit-value {
        font-size: 42px;
        font-weight: 900;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    /* Yellow accent is minimal, just a small stripe at the bottom */
    .audit-accent {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background-color: gold;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }

</style>




<div class="container">
  <div class="dashboard-header">
    <h2>Welcome to Your Dashboard</h2>
  </div>



  <div class="company-container">
    <div class="row g-4">
   

    </div>
  </div>

</div>
@endsection
