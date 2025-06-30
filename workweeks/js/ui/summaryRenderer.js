// Summary Renderer class
class SummaryRenderer {
    updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours) {
        if (!data || data.length === 0) {
            $('#dataSummary').html('<strong>No data available</strong>');
            return;
        }

        const totalWeight = Calculator.calculateTotalWeight(data);
        const completedWeight = Calculator.calculateCompletedWeight(data);
        const remainingWeight = totalWeight - completedWeight;
        const remainingTons = Math.round(remainingWeight / 200) / 10;
        const totalTons = Math.round(totalWeight / 200) / 10;
        const hoursPerTon = Calculator.safeDivide(totalJobHours, totalTons);
        const lbsPerHour = Calculator.safeDivide(totalWeight, totalJobHours);

        const percentageCompleteByHours = Calculator.safeDivide(totalUsedHours * 100, totalJobHours);
        const percentageCompleteByWeight = Calculator.safeDivide(completedWeight * 100, totalWeight);

        // Update hours summary
        $('#hoursSummary').html(`
            Visible Total Hours (MCUT/CUT/FIT/QC): ${Formatter.formatNumberWithCommas(totalJobHours)}<br>
            Visible Hours Complete: ${Formatter.formatNumberWithCommas(totalUsedHours)} (${percentageCompleteByHours.toFixed(2)}%)<br>
            Visible Hours Remaining: ${Formatter.formatNumberWithCommas(remainingHours)}<br>
            Visible Hours per Ton: ${hoursPerTon.toFixed(2)}<span style="font-size: 0.8rem; font-weight: bold; color: #3a0202"> - 
            ${lbsPerHour.toFixed(2)} (lbs/hr)</span>
        `);

        // Update weight summary
        $('#weightSummary').html(`
            Visible Total Weight: ${Formatter.formatNumberWithCommas(totalWeight)} lbs (${totalTons} tons)<br>
            Visible Green Flag Weight: ${Formatter.formatNumberWithCommas(completedWeight)} lbs (${percentageCompleteByWeight.toFixed(2)}%)<br>
            Remaining Green Flag Weight: ${Formatter.formatNumberWithCommas(remainingWeight)} lbs (${remainingTons} tons)<br>
        `);

        const totalLineItems = data.length;
        const totalAsmQty = data.reduce((sum, item) => sum + (parseInt(item.SequenceMainMarkQuantity) || 0), 0);

        // Update assembly info section
        $('#lineItemSummary').html(`
            Total Line Items: ${Formatter.formatNumberWithCommas(totalLineItems)}<br>
            Total Assembly Qty: ${Formatter.formatNumberWithCommas(totalAsmQty)}<br>
        `);
    }
}