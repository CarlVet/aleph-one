(function() {
  'use strict';
  
  // Define positivity rate colors
  const positivityColors = {
      'high': 'rgba(185, 28, 28, 0.8)',    // Dark red (>50%)
      'medium': 'rgba(239, 68, 68, 0.8)',   // Light red (10-50%)
      'low': 'rgba(234, 179, 8, 0.8)',      // Yellow (1-10%)
      'very_low': 'rgba(34, 197, 94, 0.8)'  // Green (<1%)
  };

  // Global map variable
  let currentMap = null;

  // Create pie chart for cluster icon
  function createPieChartForCluster(positivityCounts, totalCount) {
    const size = 40;
    const radius = 16;
    const centerX = 20;
    const centerY = 20;
    
    // Filter out categories with 0 count
    const activeCategories = Object.entries(positivityCounts)
      .filter(function([category, count]) {
        return count > 0;
      })
      .map(function([category, count]) {
        return {
          category: category,
          count: count,
          color: positivityColors[category] || '#gray'
        };
      });
    
    if (activeCategories.length === 0) {
      // No categories, show empty circle
      return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
        '<circle cx="' + centerX + '" cy="' + centerY + '" r="' + radius + '" fill="none" stroke="#ccc" stroke-width="2"/>' +
        '<text x="' + centerX + '" y="' + (centerY + 4) + '" text-anchor="middle" font-size="12" font-weight="bold" fill="#666">' + totalCount + '</text>' +
        '</svg>';
    }
    
    if (activeCategories.length === 1) {
      // Single category, show colored circle
      const category = activeCategories[0];
      return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
        '<circle cx="' + centerX + '" cy="' + centerY + '" r="' + radius + '" fill="' + category.color + '" stroke="white" stroke-width="2"/>' +
        '<text x="' + centerX + '" y="' + (centerY + 4) + '" text-anchor="middle" font-size="12" font-weight="bold" fill="white">' + totalCount + '</text>' +
        '</svg>';
    }
    
    // Multiple categories, create pie chart
    let currentAngle = 0;
    const total = activeCategories.reduce(function(sum, item) {
      return sum + item.count;
    }, 0);
    
    let svgPaths = '';
    
    activeCategories.forEach(function(item) {
      const angle = (item.count / total) * 2 * Math.PI;
      const endAngle = currentAngle + angle;
      
      // Calculate arc coordinates
      const x1 = centerX + radius * Math.cos(currentAngle);
      const y1 = centerY + radius * Math.sin(currentAngle);
      const x2 = centerX + radius * Math.cos(endAngle);
      const y2 = centerY + radius * Math.sin(endAngle);
      
      // Determine if arc is large (more than 180 degrees)
      const largeArcFlag = angle > Math.PI ? 1 : 0;
      
      // Create path for pie slice
      const pathData = 'M ' + centerX + ' ' + centerY + ' L ' + x1 + ' ' + y1 + ' A ' + radius + ' ' + radius + ' 0 ' + largeArcFlag + ' 1 ' + x2 + ' ' + y2 + ' Z';
      
      svgPaths += '<path d="' + pathData + '" fill="' + item.color + '" stroke="white" stroke-width="1"/>';
      
      currentAngle = endAngle;
    });
    
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
      '<circle cx="' + centerX + '" cy="' + centerY + '" r="' + (radius + 1) + '" fill="white" stroke="#ddd" stroke-width="1"/>' +
      svgPaths +
      '<circle cx="' + centerX + '" cy="' + centerY + '" r="6" fill="white" stroke="#333" stroke-width="1.5"/>' +
      '<text x="' + centerX + '" y="' + (centerY + 4) + '" text-anchor="middle" font-size="8" font-weight="bold" fill="#333">' + totalCount + '</text>' +
      '</svg>';
  }

  // Helper function to create tooltip content for clusters
  function createClusterTooltipContent(studies, pieChartData) {
    let tooltip = '<div style="font-size: 12px;"><strong>Location: ' + studies[0].latitude.toFixed(4) + ', ' + studies[0].longitude.toFixed(4) + '</strong><br>';
    tooltip += '<strong>Total studies: ' + studies.length + '</strong><br><br>';
    
    // Add positivity rate breakdown
    tooltip += '<strong>Positivity rate distribution:</strong><br>';
    pieChartData.forEach(function(item) {
      const percentage = ((item.value / studies.length) * 100).toFixed(1);
      tooltip += '<span style="color: ' + item.color + '">●</span> ' + item.label + ': ' + item.value + ' (' + percentage + '%)<br>';
    });
    
    tooltip += '<br><strong>Study details:</strong><br>';
    studies.slice(0, 8).forEach(function(study) {
      const positivityRate = study.tested_n > 0 ? ((study.pos_n / study.tested_n) * 100).toFixed(1) : 'N/A';
      tooltip += '• ' + (study.meta_type || 'Unknown') + ' - ' + (study.pathogens && study.pathogens.species ? study.pathogens.species : 'Unknown') + ' - ' + positivityRate + '%<br>';
    });
    
    if (studies.length > 8) {
      tooltip += '... and ' + (studies.length - 8) + ' more';
    }
    
    tooltip += '</div>';
    return tooltip;
  }

  Livewire.on('filtersUpdated', (payload) => {
    console.log('Filters updated, payload:', payload);
    
    // Update charts
    if (payload.data.studiesByPathogen) {
      buildPathogensChart(payload.data.studiesByPathogen);
    }
    if (payload.data.studiesByCountry) {
      buildCountriesPieChart(payload.data.studiesByCountry);
    }
    if (payload.data.descriptive_stats && payload.data.descriptive_stats.studies_timeline) {
      buildTimelineChart(payload.data.descriptive_stats.studies_timeline);
    }
  });

  function loadDynamicWidgets(samples) {
    console.log('Loading dynamic widgets with samples:', samples);
    buildMap(samples);
  }

  function updateStatistics(stats) {
    // Update timeline chart
    buildTimelineChart(stats.studies_timeline);
  }

  function updateCharts(data) {
    buildPathogensChart(data.studiesByPathogen);
    buildCountriesPieChart(data.studiesByCountry);
  }

  // Initialize charts and map on page load
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeCharts, 500);
  });

  // Initialize charts when Livewire component loads
  document.addEventListener('livewire:initialized', function() {
    setTimeout(initializeCharts, 500);
  });

  // Initialize charts when Livewire updates
  document.addEventListener('livewire:update', function() {
    setTimeout(initializeCharts, 500);
  });

  // Also try to initialize when the page is fully loaded
  window.addEventListener('load', function() {
    setTimeout(initializeCharts, 500);
  });

  // Add a manual trigger for testing
  window.testCountryStats = function() {
    console.log('Manual test of country stats:', window.countryStats);
    buildMap([]);
  };

  function initializeCharts() {
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
      console.error('Chart.js is not loaded!');
      return;
    }
    
    // Get data from the page if available
    const timelineData = window.timelineData || {};
    const studiesByPathogen = window.studiesByPathogen || {};
    const studiesByCountry = window.studiesByCountry || {};
    
    // Check if canvas elements exist
    const pathogensCanvas = document.getElementById('pathogensChart');
    const countriesCanvas = document.getElementById('countriesPieChart');
    const timelineCanvas = document.getElementById('timelineChart');
    
    // Initialize charts
    if (Object.keys(studiesByPathogen).length > 0) {
      buildPathogensChart(studiesByPathogen);
    }

    if (Object.keys(studiesByCountry).length > 0) {
      buildCountriesPieChart(studiesByCountry);
    }
    
    if (Object.keys(timelineData).length > 0) {
      buildTimelineChart(timelineData);
    }
  }

function buildMap(samples) {
  console.log('Building map with samples:', samples);
  
  const mapContainer = document.getElementById("map");
  if (!mapContainer) {
    console.error('Map container not found');
    return;
  }
  
  // Remove existing map if it exists
  if (currentMap) {
    try {
      currentMap.remove();
    } catch (e) {
      console.log('Error removing existing map:', e);
    }
    currentMap = null;
  }
  
  // Clear the container and remove any Leaflet references
  mapContainer.innerHTML = '';
  mapContainer._leaflet_id = null;
  
  // samples contains the country statistics
  const countryStats = samples || {};
  console.log('Country stats from samples:', countryStats);
  
  if (!countryStats || Object.keys(countryStats).length === 0) {
    console.log('No country stats available, showing empty message');
    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-lg">No country data available for current filters</div>';
    return;
  }
  
  console.log('Building country statistics display with', Object.keys(countryStats).length, 'countries');
  
  // Helper: get color for positivity rate
  function getColor(rate) {
    if (rate > 50) return 'bg-red-600';
    if (rate > 20) return 'bg-orange-400';
    if (rate > 5)  return 'bg-yellow-400';
    return 'bg-green-500';
  }
  
  try {
    // Sort countries by positivity rate (highest first)
    const sortedCountries = Object.entries(countryStats)
      .sort(([,a], [,b]) => b.positivity_rate - a.positivity_rate);
    
    console.log('Sorted countries:', sortedCountries);
    
    // Create a nice grid layout
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">';
    
    sortedCountries.forEach(function([country, stats]) {
      const colorClass = getColor(stats.positivity_rate);
      const textColorClass = colorClass.replace('bg-', 'text-');
      
      html += `
        <div class="bg-white rounded-xl shadow-lg border-l-4 ${colorClass} hover:shadow-xl transition-shadow duration-300">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-gray-800">${country}</h3>
              <span class="px-3 py-1 rounded-full text-sm font-bold ${colorClass} text-white">
                ${stats.positivity_rate}%
              </span>
            </div>
            
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Studies</span>
                <span class="font-semibold text-gray-800">${stats.studies}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Tested</span>
                <span class="font-semibold text-gray-800">${stats.tested.toLocaleString()}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Positive</span>
                <span class="font-semibold text-gray-800">${stats.positive.toLocaleString()}</span>
              </div>
              
              <div class="pt-2 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <span class="text-gray-600 font-medium">Positivity Rate</span>
                  <span class="font-bold text-lg ${textColorClass}">${stats.positivity_rate}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    
    html += '</div>';
    console.log('Setting map container HTML');
    mapContainer.innerHTML = html;
  } catch (error) {
    console.error('Error building country statistics:', error);
    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-red-500 text-lg">Error displaying country statistics</div>';
  }
}

function buildPathogensChart(studiesByPathogen) {
  const ctx = document.getElementById("pathogensChart");
  if (!ctx) {
    return;
  }

  Chart.getChart(ctx)?.destroy();

  const labels = Object.keys(studiesByPathogen);
  const data = Object.values(studiesByPathogen);

  const pathogensChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [{
        label: "Studies",
        data: data,
        backgroundColor: "rgba(239, 68, 68, 0.8)",
        borderColor: "rgb(239, 68, 68)",
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        },
        x: {
          ticks: {
            maxRotation: 45
          }
        }
      }
    }
  });
}

function buildCountriesPieChart(studiesByCountry) {
  const ctx = document.getElementById("countriesPieChart");
  if (!ctx) {
    return;
  }

  Chart.getChart(ctx)?.destroy();

  const entries = Object.entries(studiesByCountry || {})
    .filter(([, v]) => typeof v === 'number' && v > 0)
    .sort(([, a], [, b]) => b - a);

  const topN = 10;
  const top = entries.slice(0, topN);
  const rest = entries.slice(topN);

  const labels = top.map(([k]) => k);
  const data = top.map(([, v]) => v);

  if (rest.length) {
    labels.push('Other');
    data.push(rest.reduce((sum, [, v]) => sum + v, 0));
  }

  const palette = [
    'rgba(59, 130, 246, 0.85)',
    'rgba(16, 185, 129, 0.85)',
    'rgba(245, 158, 11, 0.85)',
    'rgba(239, 68, 68, 0.85)',
    'rgba(168, 85, 247, 0.85)',
    'rgba(14, 165, 233, 0.85)',
    'rgba(34, 197, 94, 0.85)',
    'rgba(234, 179, 8, 0.85)',
    'rgba(244, 63, 94, 0.85)',
    'rgba(100, 116, 139, 0.85)',
    'rgba(107, 114, 128, 0.85)',
  ];

  new Chart(ctx, {
    type: "pie",
    data: {
      labels,
      datasets: [{
        label: "Studies",
        data,
        backgroundColor: labels.map((_, i) => palette[i % palette.length]),
        borderColor: "rgba(255,255,255,0.9)",
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });
}

function buildTimelineChart(timelineData) {
  const ctx = document.getElementById("timelineChart");
  if (!ctx) {
    return;
  }

  Chart.getChart(ctx)?.destroy();

  const labels = Object.keys(timelineData);
  const data = Object.values(timelineData);

  const timelineChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [{
        label: "Studies Published",
        data: data,
        borderColor: "rgba(75, 192, 192, 1)",
        backgroundColor: "rgba(75, 192, 192, 0.2)",
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: "rgba(75, 192, 192, 1)",
        pointBorderColor: "#fff",
        pointBorderWidth: 2,
        pointRadius: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
          },
        },
        x: {
          ticks: {
            maxRotation: 45,
          },
        },
      },
    },
  });
}

// DataTable setups
["studies_table", "pathogens_table", "techniques_table", "countries_table"].forEach(function(id) {
  if ($('#' + id).length && typeof $.fn.DataTable !== 'undefined') {
    $('#' + id).DataTable({
      language: { search: "Filter records:" },
      order: [[0, "desc"]],
      destroy: true,
    });
  }
});

// Modal toggles
[
  { card: "studiesCard", modal: "studiesTableModal", close: "closeStudiesTable" },
  { card: "pathogensCard", modal: "pathogensTableModal", close: "closePathogensTable" },
  { card: "techniquesCard", modal: "techniquesTableModal", close: "closeTechniquesTable" },
  { card: "countriesCard", modal: "countriesTableModal", close: "closeCountriesTable" }
].forEach(function({ card, modal, close }) {
  const cardEl = document.getElementById(card);
  const modalEl = document.getElementById(modal);
  const closeEl = document.getElementById(close);
  if (cardEl) {
    cardEl.addEventListener("click", function() {
      modalEl.classList.remove("hidden");
      modalEl.classList.add("flex");
    });
  }
  if (closeEl) {
    closeEl.addEventListener("click", function() {
      modalEl.classList.remove("flex");
      modalEl.classList.add("hidden");
    });
  }
});
})(); 