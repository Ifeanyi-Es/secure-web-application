// Bar chart
new DataTable('#employee_dashboard_table');

new Chart(document.getElementById("bar-chart-item-1"), {
  type: "bar",
  data: {
    labels: ["00:00 AM", "6:00 AM", "12:00 PM", "18:00 PM", "24:00 PM"],
    datasets: [
      {
        label: "Daily Patient Tracker",
        backgroundColor: [
          "#000000ff",
          "#5c686fff",
          "#000000ff",
          "#5c686fff",
          "#000000ff",
        ],
        data: [200, 150, 115, 165, 140],
      },
    ],
  },
});

// Bar chart
new Chart(document.getElementById("bar-chart-item-2"), {
  type: "bar",
  data: {
    labels: ["Doctors", "Nurses", "Support", "Interns", "Ambulance"],
    datasets: [
      {
        label: "Number Of Staff",
        backgroundColor: [
          "#3e95cd",
          "#8e5ea2",
          "#3cba9f",
          "#e8c3b9",
          "#c45850",
        ],
        data: [12, 18, 4, 14, 3],
      },
    ],
  },
});

new Chart(document.getElementById("pie-chart"), {
    type: 'pie',
    data: {
      labels: ["Cv not reviewed", "Interviewing"],
      datasets: [{
        label: "Internship Candidates",
        backgroundColor: ["#3e95cd","#c45850"],
        data: [2478,433]
      }]
    },
    options: {
      title: {
        display: false,
        text: 'Internship Application'
        
      }
    }
});