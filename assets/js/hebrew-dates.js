/**
 * פונקציות עזר לטיפול בתאריכים עבריים
 */

// המרת מספר (1-30) לאות עברית (א'-ל')
function numberToHebrewLetter(num) {
    if (!num || isNaN(num)) return '';
    
    const hebrewLetters = {
        1: 'א', 2: 'ב', 3: 'ג', 4: 'ד', 5: 'ה',
        6: 'ו', 7: 'ז', 8: 'ח', 9: 'ט', 10: 'י',
        11: 'יא', 12: 'יב', 13: 'יג', 14: 'יד', 15: 'טו',
        16: 'טז', 17: 'יז', 18: 'יח', 19: 'יט', 20: 'כ',
        21: 'כא', 22: 'כב', 23: 'כג', 24: 'כד', 25: 'כה',
        26: 'כו', 27: 'כז', 28: 'כח', 29: 'כט', 30: 'ל'
    };
    return hebrewLetters[num] || num.toString();
}

// המרת אות עברית (א'-ל') למספר (1-30)
function hebrewLetterToNumber(letter) {
    const letterToNumber = {
        'א': 1, 'ב': 2, 'ג': 3, 'ד': 4, 'ה': 5,
        'ו': 6, 'ז': 7, 'ח': 8, 'ט': 9, 'י': 10,
        'יא': 11, 'יב': 12, 'יג': 13, 'יד': 14, 'טו': 15,
        'טז': 16, 'יז': 17, 'יח': 18, 'יט': 19, 'כ': 20,
        'כא': 21, 'כב': 22, 'כג': 23, 'כד': 24, 'כה': 25,
        'כו': 26, 'כז': 27, 'כח': 28, 'כט': 29, 'ל': 30
    };
    return letterToNumber[letter] || parseInt(letter, 10);
}

// המרת שנה לגימטריה עברית (5784 -> תשפ"ד)
function yearToHebrewYear(year) {
    if (!year || year < 5000) return '';
    
    // הסרת אלפים (5784 -> 784)
    const shortYear = year - 5000;
    
    const hundreds = Math.floor(shortYear / 100);
    const remainder = shortYear % 100;
    const tens = Math.floor(remainder / 10);
    const ones = remainder % 10;
    
    const hundredsMap = {
        1: 'ק', 2: 'ר', 3: 'ש', 4: 'ת', 5: 'תק',
        6: 'תר', 7: 'תש', 8: 'תת', 9: 'תתק'
    };
    
    const tensMap = {
        1: 'י', 2: 'כ', 3: 'ל', 4: 'מ', 5: 'ן',
        6: 'ס', 7: 'ע', 8: 'פ', 9: 'צ'
    };
    
    const onesMap = {
        1: 'א', 2: 'ב', 3: 'ג', 4: 'ד', 5: 'ה',
        6: 'ו', 7: 'ז', 8: 'ח', 9: 'ט'
    };
    
    let result = '';
    
    if (hundreds > 0) {
        result += hundredsMap[hundreds] || '';
    }
    
    // טיפול מיוחד: טו = ט"ו (לא יה), טז = ט"ז (לא יו)
    if (tens === 1 && ones === 5) {
        result += 'ט"ו';
    } else if (tens === 1 && ones === 6) {
        result += 'ט"ז';
    } else {
        if (tens > 0) {
            result += tensMap[tens] || '';
        }
        if (ones > 0) {
            if (tens > 0) {
                result += '"' + (onesMap[ones] || '');
            } else {
                result += onesMap[ones] || '';
            }
        } else if (tens > 0 && result.indexOf('"') === -1) {
            // אם יש רק עשרות בלי אחדות, נוסיף גרש
            result = result.slice(0, -1) + "'" + result.slice(-1);
        }
    }
    
    // אם אין גרש או גרשיים, נוסיף גרש לפני האות האחרונה
    if (result.indexOf('"') === -1 && result.indexOf("'") === -1 && result.length > 1) {
        result = result.slice(0, -1) + '"' + result.slice(-1);
    }
    
    return result;
}

// המרת גימטריה עברית לשנה (תשפ"ד -> 5784)
function hebrewYearToNumber(hebrewYear) {
    if (!hebrewYear) return null;
    
    // נסה קודם אם זה מספר
    const numericYear = parseInt(hebrewYear, 10);
    if (!isNaN(numericYear) && numericYear >= 5000) {
        return numericYear;
    }
    
    // הסרת גרשיים וגרש
    const cleaned = hebrewYear.replace(/["']/g, '');
    
    const letterValues = {
        'א': 1, 'ב': 2, 'ג': 3, 'ד': 4, 'ה': 5,
        'ו': 6, 'ז': 7, 'ח': 8, 'ט': 9,
        'י': 10, 'כ': 20, 'ל': 30, 'מ': 40, 'ן': 50, 'נ': 50,
        'ס': 60, 'ע': 70, 'פ': 80, 'ף': 80, 'צ': 90, 'ץ': 90,
        'ק': 100, 'ר': 200, 'ש': 300, 'ת': 400
    };
    
    let total = 0;
    for (let i = 0; i < cleaned.length; i++) {
        const letter = cleaned[i];
        total += letterValues[letter] || 0;
    }
    
    // הוספת 5000 (ה' אלפים)
    if (total < 1000) {
        total += 5000;
    }
    
    return total;
}

// חישוב גיל על פי תאריך עברי
function calculateHebrewAge(birthDay, birthMonth, birthYear) {
    if (!birthYear) return null;
    
    // המרה למספרים אם צריך
    const dayNum = typeof birthDay === 'number' ? birthDay : hebrewLetterToNumber(birthDay);
    const yearNum = typeof birthYear === 'number' ? birthYear : hebrewYearToNumber(birthYear);
    
    if (!yearNum) return null;
    
    // השנה העברית הנוכחית (קירוב)
    const now = new Date();
    const gregorianYear = now.getFullYear();
    const currentHebrewYear = gregorianYear + 3760; // קירוב גס
    
    let age = currentHebrewYear - yearNum;
    
    // התאמה לפי חודש (קירוב - לא מדויק לחלוטין בלי המרה מלאה)
    const monthOrder = ['תשרי', 'חשון', 'כסלו', 'טבת', 'שבט', 'אדר', 'אדר א', 'אדר ב', 'ניסן', 'אייר', 'סיון', 'תמוז', 'אב', 'אלול'];
    const currentMonth = now.getMonth(); // 0-11
    const estimatedHebrewMonth = (currentMonth + 3) % 12; // קירוב גס
    
    const birthMonthIndex = monthOrder.indexOf(birthMonth);
    if (birthMonthIndex !== -1 && birthMonthIndex > estimatedHebrewMonth) {
        age--;
    }
    
    return age;
}

// בניית אפשרויות לימים (א'-ל')
function buildHebrewDayOptions(selectedValue) {
    let html = '<option value="">בחר...</option>';
    for (let i = 1; i <= 30; i++) {
        const letter = numberToHebrewLetter(i);
        const selected = (selectedValue && parseInt(selectedValue) === i) ? ' selected' : '';
        html += `<option value="${i}"${selected}>${letter}</option>`;
    }
    return html;
}

// בניית אפשרויות לשנים עבריות (בפורמט גימטריה)
function buildHebrewYearOptions(selectedValue, startYear = 5700, endYear = 5800) {
    let html = '<option value="">בחר...</option>';
    for (let year = endYear; year >= startYear; year--) {
        const hebrewDisplay = yearToHebrewYear(year);
        const selected = (selectedValue && parseInt(selectedValue) === year) ? ' selected' : '';
        html += `<option value="${year}"${selected}>${hebrewDisplay} (${year})</option>`;
    }
    return html;
}
